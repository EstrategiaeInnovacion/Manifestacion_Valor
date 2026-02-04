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

            Log::info('DocumentUploadService: Procesando archivo', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'temp_path' => $fullTempPath
            ]);

            // Validar formato VUCEM
            $validationResult = $this->validateVucemFormat($fullTempPath);
            
            $finalPath = null;
            $wasConverted = false;

            if ($validationResult['is_valid']) {
                // El archivo ya cumple con VUCEM
                Log::info('DocumentUploadService: Archivo ya válido para VUCEM');
                $finalContent = file_get_contents($fullTempPath);
                $finalPath = $tempPath; // Mantener referencia para limpieza
            } else {
                // Convertir archivo al formato VUCEM
                Log::info('DocumentUploadService: Convirtiendo archivo a formato VUCEM', [
                    'validation_errors' => $validationResult['errors']
                ]);
                
                $finalContent = $this->convertToBase64($fullTempPath);
                $wasConverted = true;
                $finalPath = $tempPath; // Mantener referencia para limpieza
            }

            // Validar contenido final (crear archivo temporal para validación)
            $tempValidationPath = 'tmp/validation_' . uniqid() . '.pdf';
            $tempValidationFullPath = Storage::path($tempValidationPath);
            file_put_contents($tempValidationFullPath, $finalContent);
            
            $finalValidation = $this->validateVucemFormat($tempValidationFullPath);
            
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
    public function validateVucemFormat(string $filePath): array
    {
        $errors = [];
        $isValid = true;

        try {
            // 1. Validar tamaño (máx 3MB)
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
                $colorCheck = $this->checkGrayscale($filePath);
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

        // Convertir usando VucemPdfConverter
        $this->converter->convertToVucem($inputPath, $convertedFullPath);

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
    protected function checkGrayscale(string $filePath): array
    {
        $gsPath = $this->findGhostscript();
        
        if (!$gsPath) {
            return [
                'is_grayscale' => false,
                'detail' => 'Ghostscript no disponible'
            ];
        }

        try {
            $process = new Process([
                $gsPath,
                '-q',
                '-dNODISPLAY',
                '-dNOSAFER',
                '-c',
                "(" . str_replace('\\', '/', $filePath) . ") (r) file runpdfbegin",
                '-c',
                '1 1 pdfpagecount { pdfgetpage /Contents get dup type /arraytype eq { { .inkcoverage 4 1 roll pop pop pop 0.001 gt { (Color found) = quit } if } forall } { .inkcoverage 4 1 roll pop pop pop 0.001 gt { (Color found) = quit } if } ifelse } for',
                '-c',
                '(Grayscale) = quit'
            ]);
            
            $process->setTimeout(config('pdftools.timeouts.validation', 30));
            $process->run();
            
            $output = trim($process->getOutput());
            $isGrayscale = !str_contains($output, 'Color found');
            
            return [
                'is_grayscale' => $isGrayscale,
                'detail' => $isGrayscale ? 'Escala de grises' : 'Contiene colores'
            ];
            
        } catch (\Exception $e) {
            return [
                'is_grayscale' => false,
                'detail' => 'Error verificando colores: ' . $e->getMessage()
            ];
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
            
        } catch (\Exception $e) {
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