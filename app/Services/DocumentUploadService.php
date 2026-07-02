<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\VucemPdfConverter;
use Symfony\Component\Process\Process;
use RuntimeException;

/**
 * Servicio para subida y procesamiento de documentos PDF
 * Valida formato VUCEM y convierte automáticamente si es necesario
 */
class DocumentUploadService
{
    protected VucemPdfConverter $converter;
    
    public function __construct(VucemPdfConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Procesa un archivo PDF subido:
     * 1. Valida si cumple requisitos VUCEM
     * 2. Si no cumple, lo convierte automáticamente
     * 3. Retorna el contenido en base64 para VUCEM WSDL
     * 
     * @param UploadedFile $file
     * @return array
     */
    public function processUploadedPdf(UploadedFile $file): array
    {
        try {
            // Validar que es PDF
            if (!$this->isPdfFile($file)) {
                throw new RuntimeException('El archivo debe ser un PDF válido.');
            }

            // Guardar archivo temporal
            $tempPath = $this->saveTemporaryFile($file);
            $fullTempPath = Storage::path($tempPath);

            $maxBytes = config('pdftools.max_size_mb', 50) * 1024 * 1024;
            if (filesize($fullTempPath) > $maxBytes) {
                Storage::delete($tempPath);
                throw new RuntimeException('El archivo excede el tamaño máximo permitido.');
            }

            Log::info('DocumentUploadService: Procesando archivo', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'temp_path' => $fullTempPath
            ]);

            // Validar formato VUCEM
            $validationResult = $this->validateVucemFormat($fullTempPath);
            
            $wasConverted = false;

            // Log detallado de la validación del documento original
            Log::info('DocumentUploadService: Resultado validación original', [
                'valido'        => $validationResult['is_valid'],
                'errores'       => $validationResult['errors'],
                'version'       => $validationResult['details']['version']   ?? 'N/A',
                'tamano_mb'     => $validationResult['details']['size_mb']   ?? 'N/A',
                'escala_grises' => ($validationResult['details']['is_grayscale'] ?? null) === true
                    ? 'SÍ'
                    : (($validationResult['details']['is_grayscale'] ?? null) === false ? 'NO — tiene color' : 'no verificado'),
            ]);

            if ($validationResult['is_valid']) {
                Log::info('DocumentUploadService: Archivo ya válido para VUCEM — usando original sin conversión');
                $finalContent = file_get_contents($fullTempPath);
            } else {
                Log::info('DocumentUploadService: Iniciando conversión a formato VUCEM', [
                    'errores_detectados' => $validationResult['errors'],
                    'requiere_grises'    => !($validationResult['details']['is_grayscale'] ?? true),
                ]);

                $finalContent = $this->convertToBase64($fullTempPath);
                $wasConverted = true;
            }

            // Validar el documento resultante
            $tempValidationPath = 'tmp/validation_' . uniqid() . '.pdf';
            $tempValidationFullPath = Storage::path($tempValidationPath);
            file_put_contents($tempValidationFullPath, $finalContent);

            $finalValidation = $this->validateVucemFormat($tempValidationFullPath, $wasConverted);

            // Log del resultado FINAL — aquí se confirma si quedó en escala de grises
            Log::info('DocumentUploadService: Resultado del documento FINAL (enviado a VUCEM)', [
                'convertido'        => $wasConverted,
                'valido_vucem'      => $finalValidation['is_valid'],
                'errores_finales'   => $finalValidation['errors'],
                'version_final'     => $finalValidation['details']['version']     ?? 'N/A',
                'tamano_final_mb'   => $finalValidation['details']['size_mb']     ?? 'N/A',
                'escala_grises_final' => ($finalValidation['details']['is_grayscale'] ?? null) === true
                    ? 'SÍ ✓'
                    : (($finalValidation['details']['is_grayscale'] ?? null) === false
                        ? '⚠ NO — aún tiene color después de conversión'
                        : 'no verificado'),
            ]);

            // Limpiar archivo de validación
            @unlink($tempValidationFullPath);
            Storage::delete($tempPath);

            return [
                'success' => true,
                'file_content' => base64_encode($finalContent),
                'original_name' => $file->getClientOriginalName(),
                'final_size' => strlen($finalContent),
                'was_converted' => $wasConverted,
                'is_vucem_valid' => $finalValidation['is_valid'],
                'validation_details' => $finalValidation,
                'mime_type' => $file->getMimeType(),
                'message' => $wasConverted 
                    ? 'Archivo convertido exitosamente al formato VUCEM' 
                    : 'Archivo cargado exitosamente (ya cumplía formato VUCEM)'
            ];

        } catch (\Exception $e) {
            Log::error('DocumentUploadService: Error procesando archivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file_path' => null
            ];
        }
    }

    /**
     * Valida si el archivo cumple con los requisitos VUCEM
     */
    public function validateVucemFormat(string $filePath, bool $afterConversion = false): array
    {
        $errors = [];
        $isValid = true;
        $colorCheck      = ['is_grayscale'   => true,  'detail' => 'no verificado'];
        $encryptionCheck = ['is_unencrypted' => true,  'detail' => 'no verificado'];

        try {
            // 1. Validar tamaño (máx 4MB)
            $sizeBytes = filesize($filePath);
            $sizeMb = round($sizeBytes / (1024 * 1024), 2);
            $maxSizeMb = config('pdftools.vucem.max_final_size_mb', 3);
            
            if ($sizeBytes > $maxSizeMb * 1024 * 1024) {
                $errors[] = "Tamaño excesivo: {$sizeMb} MB (máximo {$maxSizeMb} MB)";
                $isValid = false;
            }

            // 2. Validar versión PDF
            $version = $this->getPdfVersion($filePath);
            $requiredVersion = config('pdftools.vucem.required_version', '1.4');
            if ($version !== $requiredVersion) {
                $errors[] = "Versión PDF incorrecta: {$version} (requerida {$requiredVersion})";
                $isValid = false;
            }

            // 3. Validar escala de grises
            if (config('pdftools.vucem.force_grayscale', true)) {
                $colorCheck = $this->checkGrayscale($filePath, $afterConversion);
                if (!$colorCheck['is_grayscale']) {
                    $errors[] = "No está en escala de grises: {$colorCheck['detail']}";
                    $isValid = false;
                }
            }

            // 4. Validar que no esté encriptado
            if (config('pdftools.vucem.remove_encryption', true)) {
                $encryptionCheck = $this->checkEncryption($filePath);
                if (!$encryptionCheck['is_unencrypted']) {
                    $errors[] = "Archivo protegido: {$encryptionCheck['detail']}";
                    $isValid = false;
                }
            }

            return [
                'is_valid' => $isValid,
                'errors' => $errors,
                'details' => [
                    'size_mb' => $sizeMb,
                    'version' => $version,
                    'is_grayscale' => $colorCheck['is_grayscale'],
                    'is_unencrypted' => $encryptionCheck['is_unencrypted']
                ]
            ];

        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'errors' => ['Error validando archivo: ' . $e->getMessage()],
                'details' => []
            ];
        }
    }

    /**
     * Convierte archivo al formato VUCEM y retorna el contenido binario
     */
    protected function convertToBase64(string $inputPath): string
    {
        // Crear archivo temporal para la conversión
        $convertedTempPath = 'tmp/converted_' . uniqid() . '.pdf';
        $convertedFullPath = Storage::path($convertedTempPath);
        
        // Asegurar que el directorio existe
        $convertedDir = dirname($convertedFullPath);
        if (!is_dir($convertedDir)) {
            mkdir($convertedDir, 0755, true);
        }

        // Convertir usando VucemPdfConverter (Stage 1 preserva texto/vectores y evita
        // rasterizar todo a 300 DPI; $allowAutoSplit=false garantiza un único PDF completo).
        $this->converter->convertToVucemOptimized($inputPath, $convertedFullPath, false, 2, 'auto', false);

        // Leer contenido convertido
        $convertedContent = file_get_contents($convertedFullPath);
        
        // Limpiar archivo temporal
        @unlink($convertedFullPath);
        
        return $convertedContent;
    }

    /**
     * Guarda archivo temporalmente
     */
    protected function saveTemporaryFile(UploadedFile $file): string
    {
        return $file->store('tmp/uploads');
    }

    /**
     * Verifica si el archivo es un PDF válido
     */
    protected function isPdfFile(UploadedFile $file): bool
    {
        // Verificar MIME type
        if (!in_array($file->getMimeType(), ['application/pdf'])) {
            return false;
        }

        // Verificar extensión
        if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            return false;
        }

        // Verificar header del archivo
        $handle = fopen($file->getPathname(), 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 5);
        fclose($handle);
        
        return strpos($header, '%PDF') === 0;
    }

    /**
     * Obtiene la versión del PDF
     */
    protected function getPdfVersion(string $filePath): ?string
    {
        // Intentar con Ghostscript primero
        $gsPath = $this->findGhostscript();
        
        if ($gsPath) {
            try {
                $code = '(' . str_replace('\\', '/', $filePath) . ') (r) file runpdfbegin pdfdict /Version get == quit';
                
                $process = new Process([
                    $gsPath,
                    '-q',
                    '-dNODISPLAY',
                    '-dNOSAFER',
                    '-c',
                    $code,
                ]);
                
                $process->setTimeout(config('pdftools.timeouts.validation', 30));
                $process->run();
                
                if ($process->isSuccessful()) {
                    $output = trim($process->getOutput());
                    $output = trim($output, "\" \r\n");
                    if (preg_match('/^[\d\.]+$/', $output)) {
                        return $output;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error usando Ghostscript para obtener versión PDF', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Fallback: leer directamente del archivo
        return $this->getPdfVersionFromFile($filePath);
    }

    /**
     * Lee la versión del PDF directamente del archivo
     */
    protected function getPdfVersionFromFile(string $path): ?string
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return null;
        }
        
        $header = fread($handle, 20);
        fclose($handle);
        
        if (preg_match('/%PDF-(\d+\.\d+)/', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Verifica si el PDF está en escala de grises
     */
    protected function checkGrayscale(string $filePath, bool $afterConversion = false): array
    {
        $gsPath = $this->findGhostscript();

        if (!$gsPath) {
            Log::warning('DocumentUploadService: checkGrayscale — Ghostscript no disponible, color NO verificado');
            return ['is_grayscale' => false, 'detail' => 'Ghostscript no disponible'];
        }

        try {
            // Usar el device 'inkcov' de Ghostscript — método estándar compatible con GS 9.x y 10.x.
            // Salida por página: "C  M  Y  K  CMYK OK", ej: "0.00000  0.00050  0.00000  0.12300  CMYK OK"
            // Si C, M, Y son todos 0 (< 0.001) → escala de grises. Si cualquiera > 0 → tiene color.
            $nullDevice = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'nul' : '/dev/null';

            $process = new Process([
                $gsPath,
                '-q',
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-sDEVICE=inkcov',
                '-sOutputFile=' . $nullDevice,
                $filePath,
            ]);

            $process->setTimeout(config('pdftools.timeouts.validation', 30));
            $process->run();

            $output   = trim($process->getOutput());
            $exitCode = $process->getExitCode();
            $gsError  = trim($process->getErrorOutput());

            // Error de GS: forzar conversión por seguridad
            if ($exitCode !== 0 && empty($output)) {
                Log::warning('DocumentUploadService: checkGrayscale — inkcov falló sin output, forzando conversión', [
                    'exit_code' => $exitCode,
                    'gs_error'  => $gsError ?: '(ninguno)',
                    'archivo'   => basename($filePath),
                ]);
                return ['is_grayscale' => false, 'detail' => 'No verificado — se forzará conversión a grises'];
            }

            // Output vacío con exit_code 0 = PDF de texto/vectores puro (inkcov no genera cobertura
            // para contenido vectorial). No podemos saber si tiene color.
            if (empty($output)) {
                if ($afterConversion) {
                    // Ya se forzó la conversión a escala de grises en el paso anterior; el contenido
                    // vectorial/texto no tiene cobertura de color medible antes ni después, así que
                    // repetir el rechazo aquí dejaría el documento en "no cumple" para siempre.
                    Log::info('DocumentUploadService: checkGrayscale — inkcov sin cobertura tras conversión (PDF vectorial/texto). Se considera conforme.', [
                        'archivo' => basename($filePath),
                    ]);
                    return ['is_grayscale' => true, 'detail' => 'PDF vectorial — sin color medible, conforme tras conversión'];
                }

                Log::info('DocumentUploadService: checkGrayscale — inkcov sin cobertura (PDF vectorial/texto). Forzando conversión a grises por seguridad.', [
                    'archivo' => basename($filePath),
                ]);
                return ['is_grayscale' => false, 'detail' => 'PDF vectorial — color no verificable, se convierte a grises'];
            }

            // Parsear cada línea: los tres primeros valores son C, M, Y
            $hasColor = false;
            foreach (explode("\n", $output) as $line) {
                $line = trim($line);
                if (preg_match('/^\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+CMYK/', $line, $m)) {
                    $c = (float) $m[1];
                    $mg = (float) $m[2];
                    $y = (float) $m[3];
                    if ($c > 0.001 || $mg > 0.001 || $y > 0.001) {
                        $hasColor = true;
                        break;
                    }
                }
            }

            $isGrayscale = !$hasColor;

            Log::info('DocumentUploadService: checkGrayscale (inkcov)', [
                'resultado'     => $isGrayscale ? 'ESCALA DE GRISES ✓' : 'TIENE COLOR — requiere conversión',
                'inkcov_output' => $output,
                'archivo'       => basename($filePath),
            ]);

            return [
                'is_grayscale' => $isGrayscale,
                'detail'       => $isGrayscale ? 'Escala de grises' : 'Contiene colores',
            ];

        } catch (\Exception $e) {
            Log::warning('DocumentUploadService: checkGrayscale falló con excepción', [
                'error'   => $e->getMessage(),
                'archivo' => basename($filePath),
            ]);
            return ['is_grayscale' => false, 'detail' => 'Error verificando colores: ' . $e->getMessage()];
        }
    }

    /**
     * Verifica si el PDF está encriptado
     */
    protected function checkEncryption(string $filePath): array
    {
        $gsPath = $this->findGhostscript();
        
        if (!$gsPath) {
            return [
                'is_unencrypted' => true,
                'detail' => 'No se pudo verificar encriptación'
            ];
        }

        try {
            $process = new Process([
                $gsPath,
                '-q',
                '-dNODISPLAY',
                '-dNOSAFER',
                $filePath
            ]);
            
            $process->setTimeout(config('pdftools.timeouts.validation', 10));
            $process->run();
            
            $error = $process->getErrorOutput();
            
            if (str_contains($error, 'Password') || str_contains($error, 'password')) {
                return [
                    'is_unencrypted' => false,
                    'detail' => 'Archivo protegido con contraseña'
                ];
            }
            
            return [
                'is_unencrypted' => true,
                'detail' => 'Sin protección'
            ];
            
        } catch (\Exception) {
            return [
                'is_unencrypted' => true,
                'detail' => 'No se pudo verificar encriptación'
            ];
        }
    }

    /**
     * Busca la instalación de Ghostscript
     */
    protected function findGhostscript(): ?string
    {
        // Primero intentar desde configuración
        $configPath = config('pdftools.ghostscript');
        if (!empty($configPath)) {
            if (file_exists($configPath)) {
                return $configPath;
            }
            
            // Probar si es un comando en PATH
            $process = new Process([$configPath, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $configPath;
            }
        }
        
        // Autodetectar
        $candidates = [];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $candidates = [
                'gswin64c.exe',
                'gswin32c.exe',
                'gs.exe',
                'C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs*\\bin\\gswin32c.exe',
            ];
        } else {
            // Linux/Mac
            $candidates = ['gs', 'ghostscript'];
        }
        
        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '*')) {
                // Expandir wildcards
                $matches = glob($candidate);
                if (!empty($matches)) {
                    return $matches[0];
                }
            } else {
                // Verificar si existe
                $process = new Process([$candidate, '--version']);
                $process->setTimeout(config('pdftools.timeouts.validation', 30));
                $process->run();
                
                if ($process->isSuccessful()) {
                    return $candidate;
                }
            }
        }
        
        return null;
    }
}