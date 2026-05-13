<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * Convertidor de PDF para cumplir con requisitos VUCEM
 * 
 * VUCEM requiere:
 * - PDF versión 1.4
 * - Todas las imágenes a 300 DPI exactos
 * - Escala de grises
 * - Sin contraseña
 * - Máximo 3MB
 */
class VucemPdfConverter
{
    protected ?string $ghostscriptPath = null;
    protected ?string $pdfimagesPath = null;
    protected ?string $imageMagickPath = null;
    protected bool $isWindows;

    public function __construct()
    {
        // Detectar sistema operativo una sola vez al inicializar
        $this->isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Primero intentar obtener rutas desde config/env, si no autodetectar
        $this->ghostscriptPath = $this->getConfiguredPath('ghostscript') ?: $this->findGhostscript();
        $this->pdfimagesPath = $this->getConfiguredPath('pdfimages') ?: $this->findPdfimages();
        $this->imageMagickPath = $this->getConfiguredPath('imagemagick') ?: $this->findImageMagick();
    }
    
    /**
     * Obtiene la ruta configurada en .env/config si existe y es válida
     */
    protected function getConfiguredPath(string $tool): ?string
    {
        $path = config("pdftools.{$tool}");
        
        if (empty($path)) {
            return null;
        }
        
        // Verificar que la ruta existe o que el comando es ejecutable
        if (file_exists($path)) {
            return $path;
        }
        
        // Si no es un archivo, podría ser un comando en PATH (ej: 'gs', 'pdfimages')
        $versionArg = $tool === 'pdfimages' ? '-v' : '--version';
        $process = new Process([$path, $versionArg]);
        $process->run();
        
        if ($process->isSuccessful() || str_contains($process->getErrorOutput(), $tool)) {
            return $path;
        }
        
        return null;
    }

    /**
     * Detecta la orientación de una página basándose en sus dimensiones
     * 
     * @param string $imagePath Ruta de la imagen a analizar
     * @return string 'L' para landscape (horizontal), 'P' para portrait (vertical)
     */
    protected function detectPageOrientation(string $imagePath): string
    {
        list($widthPx, $heightPx) = getimagesize($imagePath);
        
        // Si el ancho es mayor que el alto, es horizontal (landscape)
        // Si el alto es mayor o igual al ancho, es vertical (portrait)
        return ($widthPx > $heightPx) ? 'L' : 'P';
    }

    /**
     * Convierte un PDF al formato VUCEM (300 DPI exactos, escala de grises, PDF 1.4)
     * 
     * ESTRATEGIA MEJORADA: Rasterizar completamente cada página a exactamente 300 DPI
     * como imagen PNG en escala de grises, luego reconstruir el PDF.
     * Esto garantiza que TODO (texto, imágenes, vectores) esté a exactamente 300 DPI.
     * 
     * @param string $inputPath Ruta del archivo PDF de entrada
     * @param string $outputPath Ruta del archivo PDF de salida
     * @param bool $splitEnabled Si se debe dividir el PDF en partes
     * @param int $numberOfParts Número de partes en las que dividir (2-8)
     * @param string $forceOrientation Orientación forzada: 'auto', 'portrait', 'landscape'
     * @return array Información sobre los archivos generados
     */
    public function convertToVucem(string $inputPath, string $outputPath, bool $splitEnabled = false, int $numberOfParts = 2, string $forceOrientation = 'auto'): array
    {
        // Aumentar límite de tiempo y memoria de ejecución
        set_time_limit(1200); // 20 minutos
        ini_set('max_execution_time', '1200');
        ini_set('memory_limit', '2048M'); // 2GB de memoria
        
        if (!file_exists($inputPath)) {
            throw new RuntimeException("El archivo de entrada no existe: {$inputPath}");
        }

        if (!$this->ghostscriptPath) {
            throw new RuntimeException('Ghostscript no está disponible en el sistema.');
        }

        $tempDir = $this->createTempDirectory();

        try {
            // ESTRATEGIA DEFINITIVA: Rasterizar completamente a JPEG y crear PDF con TCPDF
            // Esta es la ÚNICA forma de garantizar 300 DPI exactos en TODAS las imágenes
            
            Log::info('VucemConverter: Rasterización completa a JPEG 300 DPI', [
                'input' => basename($inputPath)
            ]);
            
            // Paso 1: Generar TODOS los JPEGs con calidad MUY baja para archivos pequeños
            $jpegPattern = $tempDir . '/page_%03d.jpg';
            $gsJpegArgs = [
                '-sDEVICE=jpeggray',
                '-r300',  // 300 DPI reales
                '-dJPEGQ=15',  // Calidad 15% - compresión extrema para archivos muy pequeños
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-dQUIET',
                '-sOutputFile=' . $jpegPattern,
                $inputPath,
            ];
            
            $this->executeGhostscript($gsJpegArgs);
            
            $jpegFiles = glob($tempDir . '/page_*.jpg');
            sort($jpegFiles, SORT_NATURAL);
            $totalPages = count($jpegFiles);
            
            if ($totalPages === 0) {
                throw new RuntimeException('No se generaron páginas JPEG');
            }
            
            Log::info('VucemConverter: JPEGs generados a 300 DPI', [
                'count' => $totalPages,
                'dpi' => '300',
                'quality' => '15%'
            ]);
            
            // Paso 2: Verificar si se solicitó división personalizada
            if ($splitEnabled && $numberOfParts >= 2 && $numberOfParts <= 18) {
                // División personalizada en N partes
                Log::info("VucemConverter: División personalizada solicitada en {$numberOfParts} partes");
                
                $pagesPerPart = ceil($totalPages / $numberOfParts);
                $groups = array_chunk($jpegFiles, $pagesPerPart);
                $outputFiles = [];
                
                foreach ($groups as $groupIndex => $groupJpegs) {
                    $groupNumber = $groupIndex + 1;
                    Log::info("VucemConverter: Procesando parte {$groupNumber}/{$numberOfParts}");
                    
                    $groupOutput = str_replace('.pdf', "_parte{$groupNumber}.pdf", $outputPath);

                    $this->rebuildPdfFromJpegs($groupJpegs, $groupOutput, 100, $forceOrientation, $tempDir);

                    $sizeMB = file_exists($groupOutput) ? round(filesize($groupOutput) / (1024 * 1024), 2) : 0;
                    Log::info("VucemConverter: Parte {$groupNumber} creada - {$sizeMB} MB, " . count($groupJpegs) . " páginas");
                    
                    $outputFiles[] = [
                        'path' => $groupOutput,
                        'size' => $sizeMB,
                        'pages' => count($groupJpegs),
                        'part' => $groupNumber
                    ];
                }
                
                $totalSize = array_sum(array_column($outputFiles, 'size'));
                Log::info("VucemConverter: División personalizada completada", [
                    'parts' => count($outputFiles),
                    'total_size_mb' => round($totalSize, 2),
                    'total_pages' => $totalPages
                ]);
                
                // Retornar información de archivos divididos
                return [
                    'success' => true,
                    'split_files' => $outputFiles,
                    'total_pages' => $totalPages
                ];
                
            } elseif ($totalPages > 10) {
                // PDF grande: dividir en grupos de 10 páginas (lógica original)
                Log::info("VucemConverter: PDF grande ({$totalPages} páginas), dividiendo en grupos de 10");
                
                $groups = array_chunk($jpegFiles, 10);
                $outputFiles = [];
                
                foreach ($groups as $groupIndex => $groupJpegs) {
                    $groupNumber = $groupIndex + 1;
                    $totalGroups = count($groups);
                    Log::info("VucemConverter: Procesando grupo {$groupNumber}/{$totalGroups}");
                    
                    $groupOutput = str_replace('.pdf', "_parte{$groupNumber}.pdf", $outputPath);

                    $this->rebuildPdfFromJpegs($groupJpegs, $groupOutput, 100, $forceOrientation, $tempDir);

                    $sizeMB = file_exists($groupOutput) ? round(filesize($groupOutput) / (1024 * 1024), 2) : 0;
                    Log::info("VucemConverter: Grupo {$groupNumber} creado - {$sizeMB} MB, " . count($groupJpegs) . " páginas");
                    
                    $outputFiles[] = [
                        'path' => $groupOutput,
                        'size' => $sizeMB,
                        'pages' => count($groupJpegs),
                        'part' => $groupNumber
                    ];
                }
                
                $totalSize = array_sum(array_column($outputFiles, 'size'));
                Log::info("VucemConverter: División completada", [
                    'parts' => count($outputFiles),
                    'total_size_mb' => round($totalSize, 2),
                    'total_pages' => $totalPages
                ]);
                
                // Unir todas las partes en un solo PDF usando Ghostscript
                Log::info("VucemConverter: Uniendo todas las partes en un solo PDF...");
                
                $partsForMerge = array_column($outputFiles, 'path');
                
                $gsMergeArgs = [
                    '-sDEVICE=pdfwrite',
                    '-dCompatibilityLevel=1.4',
                    '-dNOPAUSE',
                    '-dBATCH',
                    '-dSAFER',
                    '-dQUIET',
                    '-sOutputFile=' . $outputPath,
                ];
                
                // Agregar todos los archivos de partes
                foreach ($partsForMerge as $partFile) {
                    $gsMergeArgs[] = $partFile;
                }
                
                $this->executeGhostscript($gsMergeArgs);
                
                if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
                    throw new RuntimeException('No se pudo unir los PDFs divididos');
                }
                
                $finalSizeMB = round(filesize($outputPath) / (1024 * 1024), 2);
                Log::info("VucemConverter: PDF unificado creado", [
                    'size_mb' => $finalSizeMB,
                    'parts_merged' => count($partsForMerge)
                ]);
                
                // Limpiar archivos de partes temporales
                foreach ($partsForMerge as $partFile) {
                    @unlink($partFile);
                }
                
                $success = true;
                
            } else {
                // PDF pequeño: intentar ajustar a 10 MB con diferentes calidades
                Log::info('VucemConverter: PDF pequeño (<=10 páginas), ajustando calidad');
                
                $jpegQualities = [75, 65, 55, 50];
                $success = false;
                
                foreach ($jpegQualities as $index => $quality) {
                    $attempt = $index + 1;
                    Log::info("VucemConverter: Intento {$attempt}/" . count($jpegQualities) . " - calidad {$quality}%");
                    
                    if ($index > 0) {
                        $gsJpegArgs[2] = '-dJPEGQ=' . $quality;
                        $this->executeGhostscript($gsJpegArgs);
                        $jpegFiles = glob($tempDir . '/page_*.jpg');
                        sort($jpegFiles, SORT_NATURAL);
                    }
                    
                    $this->rebuildPdfFromJpegs($jpegFiles, $outputPath, $quality, $forceOrientation, $tempDir);

                    if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
                        throw new RuntimeException('No se pudo crear el PDF');
                    }

                    $sizeMB = round(filesize($outputPath) / (1024 * 1024), 2);
                    Log::info("VucemConverter: PDF creado - {$sizeMB} MB");
                    
                    if ($sizeMB <= 10.0) {
                        $success = true;
                        break;
                    }
                    
                    if ($attempt < count($jpegQualities)) {
                        Log::warning("VucemConverter: PDF excede 10 MB, reduciendo calidad...");
                        unlink($outputPath);
                        foreach ($jpegFiles as $f) @unlink($f);
                    }
                }
            }
            
            // Verificar que el PDF se generó
            if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
                throw new RuntimeException('No se pudo crear el PDF');
            }
            
            $outputSizeMB = round(filesize($outputPath) / (1024 * 1024), 2);
            
            // Contar páginas del PDF original para el log
            $pageCountResult = $this->executeGhostscript([
                '-dQUIET',
                '-dNODISPLAY',
                '-dNOSAFER',
                '-dNOPAUSE',
                '-dBATCH',
                '-c',
                "(" . $inputPath . ") (r) file runpdfbegin pdfpagecount = quit"
            ]);
            $pageCount = intval(trim($pageCountResult['output'])) ?: 0;
            
            // Solo advertir si excede 10 MB, pero NO lanzar error
            // NOTA: Para PDFs grandes que se dividieron y unieron, el archivo final puede ser mayor a 10 MB
            // pero internamente se procesó en grupos de 10 páginas con buena calidad (60%)
            if ($outputSizeMB > 10.0 && $totalPages <= 10) {
                // Solo advertir si es un PDF pequeño que no se dividió
                Log::warning('VucemConverter: ADVERTENCIA - PDF excede 10 MB VUCEM', [
                    'output_size_mb' => $outputSizeMB,
                    'pages' => $pageCount,
                    'message' => 'El PDF será descargado pero puede ser rechazado por VUCEM (límite 10 MB para imágenes)'
                ]);
            } else {
                Log::info('VucemConverter: Conversión completada exitosamente', [
                    'output_size_mb' => $outputSizeMB,
                    'pages' => $pageCount,
                    'note' => $totalPages > 10 ? 'PDF grande procesado por partes y unificado' : 'PDF procesado directamente'
                ]);
            }
            
            return [
                'success' => true,
                'output_path' => $outputPath,
                'size_mb' => $outputSizeMB,
                'pages' => $pageCount
            ];

        } finally {
            $this->cleanupDirectory($tempDir);
        }
    }

    /**
     * Combina múltiples PDFs en uno solo de forma simple y directa
     */
    protected function mergePdfsSimple(array $pdfFiles, string $outputPath): void
    {
        if (empty($pdfFiles)) {
            throw new RuntimeException('No hay archivos PDF para combinar.');
        }

        // Si solo hay un archivo, copiarlo directamente
        if (count($pdfFiles) === 1) {
            copy($pdfFiles[0], $outputPath);
            return;
        }

        // Combinar todos los PDFs usando Ghostscript con configuración estricta VUCEM
        $args = [
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',              // PDF 1.4 (NO 1.5, 1.6, 1.7)
            '-dPDFSETTINGS=/prepress',               // Máxima calidad
            '-sColorConversionStrategy=Gray',        // Todo a escala de grises
            '-dProcessColorModel=/DeviceGray',       // Solo grises
            '-dAutoFilterGrayImages=false',          // NO auto-detectar
            '-dGrayImageFilter=/FlateEncode',        // Compresión sin pérdida
            '-dGrayImageResolution=300',             // 300 DPI exactos
            '-dDownsampleGrayImages=false',          // NO reducir resolución
            '-dEncodeGrayImages=true',               // Codificar en grises
            '-dDetectDuplicateImages=false',         // Mantener todas las imágenes
            '-r300x300',                             // 300 DPI exactos
            '-sOutputFile=' . $outputPath,
        ];

        // Agregar todos los archivos PDF
        foreach ($pdfFiles as $pdf) {
            $args[] = $pdf;
        }

        $result = $this->executeGhostscript($args);

        if (!file_exists($outputPath) || filesize($outputPath) < 100) {
            throw new RuntimeException('No se pudo combinar los PDFs. Error: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    /**
     * Combina múltiples PDFs en uno solo
     */
    protected function mergePdfs(array $pdfFiles, string $outputPath, string $tempDir): void
    {
        // Si solo hay un archivo, re-procesarlo para asegurar PDF 1.4
        if (count($pdfFiles) === 1) {
            $result = $this->executeGhostscript([
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dPDFSETTINGS=/prepress',
                '-sOutputFile=' . $outputPath,
                $pdfFiles[0],
            ]);

            if (file_exists($outputPath) && filesize($outputPath) > 100) {
                return;
            }
        }

        // Método 1: Combinar directamente con Ghostscript
        $args = [
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/prepress',
            '-sOutputFile=' . $outputPath,
        ];

        foreach ($pdfFiles as $pdf) {
            $args[] = $pdf;
        }

        $result = $this->executeGhostscript($args);

        if (file_exists($outputPath) && filesize($outputPath) > 100) {
            return;
        }

        // Método 2: Crear archivo con lista de PDFs
        $listFile = $tempDir . DIRECTORY_SEPARATOR . 'filelist.txt';
        $listContent = '';
        foreach ($pdfFiles as $pdf) {
            $listContent .= $pdf . "\n";
        }
        file_put_contents($listFile, $listContent);

        $result = $this->executeGhostscript([
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-sOutputFile=' . $outputPath,
            '@' . $listFile,
        ]);

        @unlink($listFile);

        if (file_exists($outputPath) && filesize($outputPath) > 100) {
            return;
        }

        // Método 3: Concatenar PDFs uno por uno
        $currentOutput = $pdfFiles[0];
        
        for ($i = 1; $i < count($pdfFiles); $i++) {
            $nextOutput = $tempDir . DIRECTORY_SEPARATOR . 'merged_' . $i . '.pdf';
            
            $this->executeGhostscript([
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-sOutputFile=' . $nextOutput,
                $currentOutput,
                $pdfFiles[$i],
            ]);

            if ($i > 1) {
                @unlink($currentOutput);
            }
            
            $currentOutput = $nextOutput;
        }

        if (file_exists($currentOutput)) {
            // Procesar una vez más para asegurar PDF 1.4
            $this->executeGhostscript([
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-sOutputFile=' . $outputPath,
                $currentOutput,
            ]);
            @unlink($currentOutput);
        }

        if (!file_exists($outputPath) || filesize($outputPath) < 100) {
            throw new RuntimeException('No se pudieron combinar los PDFs. Último error: ' . ($result['error'] ?? 'desconocido'));
        }
    }

    /**
     * Ejecuta Ghostscript y retorna resultado
     */
    protected function executeGhostscript(array $args): array
    {
        // Construir comando - NO usar -q porque causa problemas con paths en Windows
        $command = array_merge([
            $this->ghostscriptPath,
            '-dBATCH',
            '-dNOPAUSE',
            '-dNOSAFER',
            '-dQUIET',
        ], $args);

        $process = new Process($command);
        $process->setTimeout(600);
        
        // Establecer directorio de trabajo temporal para Ghostscript
        $tempPath = sys_get_temp_dir();
        $process->setEnv([
            'TEMP' => $tempPath,
            'TMP' => $tempPath,
        ]);
        
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
            'code' => $process->getExitCode(),
        ];
    }

    /**
     * Valida que el PDF resultante tenga EXACTAMENTE 300 DPI en TODAS las imágenes
     * Validación estricta: x_dpi === 300 Y y_dpi === 300 (no promedio)
     */
    public function validateDpi(string $pdfPath): array
    {
        if (!$this->pdfimagesPath || !file_exists($pdfPath)) {
            return ['valid' => false, 'error' => 'No se puede validar - pdfimages no disponible'];
        }

        try {
            $process = new Process([$this->pdfimagesPath, '-list', $pdfPath]);
            $process->setTimeout(120);
            $process->run();

            // Si el proceso falla (exit code negativo = crash), no reportar error
            if (!$process->isSuccessful() || $process->getExitCode() < 0) {
                return [
                    'valid' => false, 
                    'error' => 'No se pudo validar DPI'
                ];
            }
        } catch (\Exception $e) {
            // Silenciar excepciones de pdfimages
            return [
                'valid' => false,
                'error' => 'No se pudo validar DPI'
            ];
        }

        $output = $process->getOutput();
        $lines = explode("\n", $output);
        $images = [];
        $allValid = true;
        $totalImages = 0;
        $invalidImages = [];

        foreach ($lines as $lineNum => $line) {
            // Detectar líneas de imágenes en el output de pdfimages -list
            if (preg_match('/^\s*(\d+)\s+(\d+)\s+(\w+)\s+(\d+)\s+(\d+)\s+(\w+)\s+(\d+)\s+(\d+)\s+\S+\s+\S+\s+\d+\s+\d+\s+(\d+)\s+(\d+)/', $line, $m)) {
                $totalImages++;
                $page = intval($m[1]);
                $xPpi = intval($m[9]);
                $yPpi = intval($m[10]);
                
                // VALIDACIÓN ESTRICTA: Ambos deben ser EXACTAMENTE 300
                $isValid = ($xPpi === 300 && $yPpi === 300);
                
                $imageInfo = [
                    'page' => $page,
                    'num' => intval($m[2]),
                    'type' => $m[3],
                    'width' => intval($m[4]),
                    'height' => intval($m[5]),
                    'x_dpi' => $xPpi,
                    'y_dpi' => $yPpi,
                    'valid' => $isValid,
                ];
                
                $images[] = $imageInfo;

                if (!$isValid) {
                    $allValid = false;
                    $invalidImages[] = $imageInfo;
                }
            }
        }

        $result = [
            'valid' => $allValid,
            'total_images' => $totalImages,
            'images' => $images,
            'invalid_images' => $invalidImages,
            'invalid_count' => count($invalidImages),
        ];

        // Si no hay imágenes detectadas, advertir
        if ($totalImages === 0) {
            $result['warning'] = 'No se detectaron imágenes en el PDF. Puede ser que el PDF solo contenga vectores o texto.';
        }

        return $result;
    }

    protected function createTempDirectory(): string
    {
        $basePath = storage_path('app/tmp/vucem_convert');
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        $tempDir = $basePath . DIRECTORY_SEPARATOR . 'conv_' . uniqid() . '_' . time();
        mkdir($tempDir, 0755, true);
        return $tempDir;
    }

    protected function cleanupDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        
        $files = glob($dir . DIRECTORY_SEPARATOR . '*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        @rmdir($dir);
    }

    protected function findGhostscript(): ?string
    {
        if ($this->isWindows) {
            // Rutas de Windows
            $gsFolders = glob('C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe');
            if ($gsFolders) {
                rsort($gsFolders, SORT_NATURAL);
                foreach ($gsFolders as $path) {
                    if (file_exists($path)) {
                        return $path;
                    }
                }
            }
            
            // Intentar en PATH de Windows
            $process = new Process(['gswin64c', '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return 'gswin64c';
            }
            
            // Intentar versión 32 bits
            $process = new Process(['gswin32c', '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return 'gswin32c';
            }
        } else {
            // Linux/Unix - Primero intentar ejecutar directamente 'gs'
            // Esto funciona si gs está en el PATH del sistema
            $process = Process::fromShellCommandline('gs --version 2>/dev/null');
            $process->run();
            if ($process->isSuccessful() && !empty(trim($process->getOutput()))) {
                return 'gs';
            }
            
            // Rutas comunes de Linux/Unix
            $linuxPaths = [
                '/usr/bin/gs',
                '/usr/local/bin/gs',
                '/opt/local/bin/gs',
                '/snap/bin/gs',
            ];
            
            foreach ($linuxPaths as $path) {
                if (file_exists($path)) {
                    // Verificar que es ejecutable probándolo
                    $process = new Process([$path, '--version']);
                    $process->run();
                    if ($process->isSuccessful()) {
                        return $path;
                    }
                }
            }
            
            // Intentar con which
            $process = Process::fromShellCommandline('which gs 2>/dev/null');
            $process->run();
            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if (!empty($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    protected function findPdfimages(): ?string
    {
        if ($this->isWindows) {
            // Rutas de Windows
            $windowsPaths = [
                'C:\\Poppler\\Release-25.12.0-0\\poppler-25.12.0\\Library\\bin\\pdfimages.exe',
                'C:\\Poppler\\Library\\bin\\pdfimages.exe',
                'C:\\Program Files\\poppler\\bin\\pdfimages.exe',
                'C:\\Program Files (x86)\\poppler\\bin\\pdfimages.exe',
            ];
            
            foreach ($windowsPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
            
            // Buscar con glob por si hay diferentes versiones
            $popplerFolders = glob('C:\\Poppler\\Release-*\\poppler-*\\Library\\bin\\pdfimages.exe');
            if ($popplerFolders) {
                rsort($popplerFolders, SORT_NATURAL);
                return $popplerFolders[0];
            }
        } else {
            // Linux/Unix - Primero intentar ejecutar directamente 'pdfimages'
            $process = Process::fromShellCommandline('pdfimages -v 2>&1');
            $process->run();
            $output = $process->getOutput() . $process->getErrorOutput();
            if (str_contains($output, 'pdfimages') || str_contains($output, 'poppler')) {
                return 'pdfimages';
            }
            
            // Rutas comunes de Linux/Unix
            $linuxPaths = [
                '/usr/bin/pdfimages',
                '/usr/local/bin/pdfimages',
                '/opt/local/bin/pdfimages',
                '/snap/bin/pdfimages',
            ];
            
            foreach ($linuxPaths as $path) {
                if (file_exists($path)) {
                    // Verificar que funciona ejecutándolo
                    $process = new Process([$path, '-v']);
                    $process->run();
                    $output = $process->getOutput() . $process->getErrorOutput();
                    if (str_contains($output, 'pdfimages') || str_contains($output, 'poppler')) {
                        return $path;
                    }
                }
            }
            
            // Intentar con which
            $process = Process::fromShellCommandline('which pdfimages 2>/dev/null');
            $process->run();
            if ($process->isSuccessful()) {
                $path = trim($process->getOutput());
                if (!empty($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Encuentra la instalación de ImageMagick en el sistema
     */
    protected function findImageMagick(): ?string
    {
        if ($this->isWindows) {
            // Windows - Intentar diferentes rutas comunes
            $windowsPaths = [
                'magick',  // Si está en PATH
                'C:\\Program Files\\ImageMagick-7.1.2-Q16-HDRI\\magick.exe',
                'C:\\Program Files\\ImageMagick-7.1.1-Q16-HDRI\\magick.exe',
                'C:\\Program Files\\ImageMagick\\magick.exe',
                'C:\\Program Files (x86)\\ImageMagick\\magick.exe',
            ];
            
            foreach ($windowsPaths as $path) {
                if ($path === 'magick' || file_exists($path)) {
                    $testProcess = new Process([$path === 'magick' ? 'magick' : $path, '-version']);
                    try {
                        $testProcess->run();
                        if ($testProcess->isSuccessful()) {
                            return $path === 'magick' ? 'magick' : $path;
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        } else {
            // Linux/Unix
            $process = Process::fromShellCommandline('convert -version 2>&1');
            $process->run();
            $output = $process->getOutput();
            if (str_contains($output, 'ImageMagick')) {
                return 'convert';
            }
            
            // Intentar con which
            $process = Process::fromShellCommandline('which convert 2>/dev/null');
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        }

        return null;
    }

    /**
     * Valida que el PDF cumpla con TODOS los requisitos de VUCEM
     */
    public function validateVucemCompliance(string $pdfPath): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        if (!file_exists($pdfPath)) {
            return ['valid' => false, 'errors' => ['El archivo no existe']];
        }

        // 1. Validar tamaño (máximo 3 MB)
        $fileSize = filesize($pdfPath);
        $maxSize = 3 * 1024 * 1024; // 3 MB
        if ($fileSize > $maxSize) {
            $result['valid'] = false;
            $sizeMB = round($fileSize / (1024 * 1024), 2);
            $result['errors'][] = "Tamaño {$sizeMB} MB excede el límite de 3 MB";
        }

        // 2. Validar versión PDF usando Ghostscript
        if ($this->ghostscriptPath) {
            $process = new Process([
                $this->ghostscriptPath,
                '-dNODISPLAY',
                '-dQUIET',
                '-dNOPAUSE',
                '-dBATCH',
                '-c',
                '(pdfPath) cvn dup where { exch get exec } { pop () } ifelse quit',
                $pdfPath,
            ]);
            $process->setTimeout(30);
            $process->run();
            
            // Leer el header del PDF directamente
            $handle = fopen($pdfPath, 'r');
            $header = fread($handle, 1024);
            fclose($handle);
            
            if (preg_match('/%PDF-(\d+\.\d+)/', $header, $matches)) {
                $version = floatval($matches[1]);
                if ($version > 1.4) {
                    $result['valid'] = false;
                    $result['errors'][] = "Versión PDF {$matches[1]} no permitida (debe ser 1.4)";
                }
            }
        }

        // 3. Validar DPI de las imágenes
        $dpiValidation = $this->validateDpi($pdfPath);
        if (isset($dpiValidation['valid']) && !$dpiValidation['valid']) {
            $result['valid'] = false;
            if (isset($dpiValidation['error'])) {
                $result['errors'][] = $dpiValidation['error'];
            }
            if (isset($dpiValidation['invalid_count']) && $dpiValidation['invalid_count'] > 0) {
                $result['errors'][] = "{$dpiValidation['invalid_count']} imágenes no tienen exactamente 300 DPI";
            }
        }

        // 4. Detectar color (esto requiere analizar el contenido)
        if ($this->pdfimagesPath) {
            $process = new Process([$this->pdfimagesPath, '-list', $pdfPath]);
            $process->setTimeout(60);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                // Buscar imágenes en color (no 'gray')
                if (preg_match('/\s+(rgb|cmyk|icc|idx|jpeg|jp2)\s+/i', $output)) {
                    $result['warnings'][] = 'El PDF puede contener imágenes en color';
                }
            }
        }

        return $result;
    }

    public function getToolsInfo(): array
    {
        return [
            'os' => [
                'type' => $this->isWindows ? 'Windows' : 'Linux/Unix',
                'php_os' => PHP_OS,
            ],
            'ghostscript' => [
                'available' => $this->ghostscriptPath !== null,
                'path' => $this->ghostscriptPath,
            ],
            'pdfimages' => [
                'available' => $this->pdfimagesPath !== null,
                'path' => $this->pdfimagesPath,
            ],
        ];
    }

    /**
     * Información de debug para diagnóstico en producción
     */
    public function getDebugInfo(): array
    {
        $debug = [
            'os' => [
                'php_os' => PHP_OS,
                'is_windows' => $this->isWindows,
                'uname' => php_uname(),
            ],
            'paths_checked' => [],
            'commands_tested' => [],
        ];

        if (!$this->isWindows) {
            // Verificar rutas de Ghostscript
            $gsPaths = ['/usr/bin/gs', '/usr/local/bin/gs', '/opt/local/bin/gs', '/snap/bin/gs'];
            foreach ($gsPaths as $path) {
                $debug['paths_checked']['gs'][$path] = [
                    'exists' => file_exists($path),
                    'is_executable' => is_executable($path),
                ];
            }

            // Verificar rutas de pdfimages
            $pdfPaths = ['/usr/bin/pdfimages', '/usr/local/bin/pdfimages', '/opt/local/bin/pdfimages'];
            foreach ($pdfPaths as $path) {
                $debug['paths_checked']['pdfimages'][$path] = [
                    'exists' => file_exists($path),
                    'is_executable' => is_executable($path),
                ];
            }

            // Probar comandos directamente
            $commands = [
                'gs_version' => 'gs --version 2>&1',
                'which_gs' => 'which gs 2>&1',
                'pdfimages_version' => 'pdfimages -v 2>&1',
                'which_pdfimages' => 'which pdfimages 2>&1',
                'path_env' => 'echo $PATH',
                'whoami' => 'whoami',
            ];

            foreach ($commands as $key => $cmd) {
                $process = Process::fromShellCommandline($cmd);
                $process->run();
                $debug['commands_tested'][$key] = [
                    'command' => $cmd,
                    'success' => $process->isSuccessful(),
                    'exit_code' => $process->getExitCode(),
                    'output' => trim($process->getOutput()),
                    'error' => trim($process->getErrorOutput()),
                ];
            }
        }

        $debug['final_paths'] = [
            'ghostscript' => $this->ghostscriptPath,
            'pdfimages' => $this->pdfimagesPath,
        ];

        return $debug;
    }

    /**
     * Comprime un PDF sin rasterizar, manteniendo 300 DPI
     * 
     * @param string $inputPath Ruta del archivo PDF de entrada
     * @param string $outputPath Ruta del archivo PDF de salida
     * @param string $level Nivel de compresión: screen, ebook, printer, prepress
     * @return array Información sobre la compresión
     */
    public function compressPdf(string $inputPath, string $outputPath, string $level = 'printer'): array
    {
        if (!file_exists($inputPath)) {
            throw new RuntimeException("El archivo de entrada no existe: {$inputPath}");
        }

        if (!$this->ghostscriptPath) {
            throw new RuntimeException('Ghostscript no está disponible en el sistema.');
        }

        // Aumentar límite de tiempo para archivos grandes
        set_time_limit(600); // 10 minutos
        ini_set('max_execution_time', '600');

        $inputSize = filesize($inputPath);
        
        // Configuración según nivel de compresión
        $settings = [
            'screen' => [
                'dpi' => 72,
                'description' => 'Pantalla - Máxima compresión (72 DPI)'
            ],
            'ebook' => [
                'dpi' => 150,
                'description' => 'Ebook - Alta compresión (150 DPI)'
            ],
            'printer' => [
                'dpi' => 300,
                'description' => 'Impresora - Mantiene 300 DPI'
            ],
            'prepress' => [
                'dpi' => 300,
                'description' => 'Preimpresión - Calidad máxima 300 DPI'
            ]
        ];

        $config = $settings[$level] ?? $settings['printer'];

        Log::info('PdfCompress: Iniciando compresión', [
            'input' => basename($inputPath),
            'level' => $level,
            'dpi' => $config['dpi'],
            'input_size_mb' => round($inputSize / (1024 * 1024), 2)
        ]);

        // Argumentos para Ghostscript con compresión optimizada
        $gsArgs = [
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/' . $level,
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',
            '-sColorConversionStrategy=Gray',
            '-dProcessColorModel=/DeviceGray',
            '-dCompressFonts=true',
            '-dCompressPages=true',
            '-dOptimize=true',
        ];

        // Configuración específica según el nivel
        if ($level === 'printer' || $level === 'prepress') {
            // Mantener 300 DPI pero comprimir con JPEG
            $gsArgs[] = '-dDownsampleGrayImages=false';
            $gsArgs[] = '-dGrayImageResolution=300';
            $gsArgs[] = '-dGrayImageDownsampleThreshold=1.0';
            $gsArgs[] = '-dAutoFilterGrayImages=false';
            $gsArgs[] = '-dGrayImageFilter=/DCTEncode';
            $gsArgs[] = '-dEncodeGrayImages=true';
            // Calidad JPEG más baja para comprimir mejor
            $gsArgs[] = '-dJPEGQ=60';
        } else {
            // Para screen y ebook, permitir downsample
            $gsArgs[] = '-dDownsampleGrayImages=true';
            $gsArgs[] = '-dGrayImageDownsampleType=/Bicubic';
            $gsArgs[] = '-dGrayImageResolution=' . $config['dpi'];
            $gsArgs[] = '-dAutoFilterGrayImages=false';
            $gsArgs[] = '-dGrayImageFilter=/DCTEncode';
            $gsArgs[] = '-dEncodeGrayImages=true';
            $gsArgs[] = '-dJPEGQ=50';
        }

        $gsArgs[] = '-sOutputFile=' . $outputPath;
        $gsArgs[] = $inputPath;

        $this->executeGhostscript($gsArgs);

        if (!file_exists($outputPath)) {
            throw new RuntimeException('No se pudo comprimir el PDF');
        }

        $outputSize = filesize($outputPath);
        $reduction = round((($inputSize - $outputSize) / $inputSize) * 100, 2);

        Log::info('PdfCompress: Compresión completada', [
            'output_size_mb' => round($outputSize / (1024 * 1024), 2),
            'reduction_percent' => $reduction,
            'level' => $level
        ]);

        return [
            'success' => true,
            'input_size' => $inputSize,
            'output_size' => $outputSize,
            'reduction_percent' => $reduction,
            'level' => $level,
            'description' => $config['description']
        ];
    }

    /**
     * Combina múltiples PDFs en uno solo sin rasterizar, manteniendo 300 DPI
     * 
     * @param array $inputPaths Array con rutas de archivos PDF a combinar
     * @param string $outputPath Ruta del archivo PDF de salida
     * @return array Información sobre la combinación
     */
    public function mergePdfsKeepDpi(array $inputPaths, string $outputPath): array
    {
        if (empty($inputPaths)) {
            throw new RuntimeException('No hay archivos PDF para combinar.');
        }

        foreach ($inputPaths as $path) {
            if (!file_exists($path)) {
                throw new RuntimeException("El archivo no existe: {$path}");
            }
        }

        if (!$this->ghostscriptPath) {
            throw new RuntimeException('Ghostscript no está disponible en el sistema.');
        }

        // Aumentar límite de tiempo para múltiples archivos
        set_time_limit(600); // 10 minutos
        ini_set('max_execution_time', '600');

        Log::info('PdfMerge: Iniciando combinación', [
            'files_count' => count($inputPaths),
            'total_size_mb' => round(array_sum(array_map('filesize', $inputPaths)) / (1024 * 1024), 2)
        ]);

        // Si solo hay un archivo, copiarlo directamente
        if (count($inputPaths) === 1) {
            copy($inputPaths[0], $outputPath);
            $outputSize = filesize($outputPath);
            
            return [
                'success' => true,
                'files_merged' => 1,
                'output_size' => $outputSize
            ];
        }

        // Combinar todos los PDFs usando Ghostscript manteniendo 300 DPI
        $gsArgs = [
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dPDFSETTINGS=/prepress',
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',
            '-sColorConversionStrategy=Gray',
            '-dProcessColorModel=/DeviceGray',
            '-dDownsampleGrayImages=false',
            '-dGrayImageResolution=300',
            '-dAutoFilterGrayImages=false',
            '-dGrayImageFilter=/FlateEncode',
            '-sOutputFile=' . $outputPath,
        ];

        // Agregar todos los archivos PDF
        foreach ($inputPaths as $pdf) {
            $gsArgs[] = $pdf;
        }

        $this->executeGhostscript($gsArgs);

        if (!file_exists($outputPath) || filesize($outputPath) < 100) {
            throw new RuntimeException('No se pudo combinar los PDFs');
        }

        $outputSize = filesize($outputPath);

        Log::info('PdfMerge: Combinación completada', [
            'output_size_mb' => round($outputSize / (1024 * 1024), 2),
            'files_merged' => count($inputPaths)
        ]);

        return [
            'success' => true,
            'files_merged' => count($inputPaths),
            'output_size' => $outputSize
        ];
    }

    /**
     * Obtiene la configuración de VUCEM
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return config("vucem.{$key}", $default);
    }

    /**
     * Convierte PDF al formato VUCEM con algoritmo de dos etapas:
     *
     * STAGE 1 — Optimización directa PDF→PDF (sin rasterizar):
     *   Preserva texto y vectores tal cual. Solo procesa imágenes:
     *   convierte a escala de grises, reduce las que superan 300 DPI y
     *   aplica compresión JPEG progresiva [70%, 50%, 35%, 20%].
     *   Resultado: PDFs con contenido vectorial/texto quedan MUCHO más
     *   pequeños que con rasterización total.
     *   Si el resultado cabe en el límite VUCEM y tiene DPI correcto → DONE.
     *
     * STAGE 2 — Rasterización completa (fallback):
     *   Convierte cada píxel a JPEG 300 DPI exactos. Necesario cuando:
     *   - Stage 1 no redujo el tamaño (doc. con imágenes dominantes).
     *   - Stage 1 produjo imágenes que no están exactamente a 300 DPI.
     *   - El usuario solicitó división explícita en N partes.
     *   CORRECCIÓN: cada intento de calidad [50%, 35%, 20%, 12%, 8%] ahora
     *   REGENERA los JPEGs con Ghostscript (antes solo cambiaba el parámetro
     *   de TCPDF, que no tiene efecto en archivos .jpg ya generados).
     *
     * @param string $inputPath Ruta del archivo PDF de entrada
     * @param string $outputPath Ruta del archivo PDF de salida
     * @param bool $splitEnabled Si se debe dividir el PDF en partes
     * @param int $numberOfParts Número de partes en las que dividir (2-8)
     * @param string $forceOrientation Orientación forzada: 'auto', 'portrait', 'landscape'
     * @return array Información detallada del proceso y resultado
     */
    public function convertToVucemOptimized(string $inputPath, string $outputPath, bool $splitEnabled = false, int $numberOfParts = 2, string $forceOrientation = 'auto'): array
    {
        set_time_limit(1200);
        ini_set('max_execution_time', '1200');
        ini_set('memory_limit', $this->getConfig('gs_memory_limit', '2048M'));

        if (!file_exists($inputPath)) {
            throw new RuntimeException("El archivo de entrada no existe: {$inputPath}");
        }

        if (!$this->ghostscriptPath) {
            throw new RuntimeException('Ghostscript no está disponible en el sistema.');
        }

        $originalSize = filesize($inputPath);
        $originalSizeMB = round($originalSize / (1024 * 1024), 2);
        $maxSize = $this->getConfig('auto_split_threshold', 3 * 1024 * 1024);
        $warnSize = $this->getConfig('warn_size', 2.5 * 1024 * 1024);
        $autoSplit = $this->getConfig('auto_split', true);

        $tempDir = $this->createTempDirectory();
        $bestStage1Path = null;

        $result = [
            'success' => false,
            'original_size' => $originalSize,
            'original_size_mb' => $originalSizeMB,
            'converted_size' => 0,
            'converted_size_mb' => 0,
            'size_change_percent' => 0,
            'was_reduced' => false,
            'compression_attempts' => 0,
            'final_quality' => null,
            'warnings' => [],
            'messages' => [],
            'auto_divided' => false,
            'parts' => [],
            'exceeded_threshold' => false,
        ];

        try {
            Log::info('VucemConverter: Iniciando conversión (Stage 1 + Stage 2)', [
                'input' => basename($inputPath),
                'original_size_mb' => $originalSizeMB,
            ]);

            // =================================================================
            // STAGE 1: Optimización directa PDF→PDF (preserva vectores/texto)
            // Se ejecuta SIEMPRE, incluyendo cuando el usuario pide dividir.
            // Para docs con texto/vectores, Stage 1 evita el crecimiento 4MB→20MB
            // que ocurre al rasterizar todo a 300 DPI.
            // Si Stage 1 produce un resultado DPI-válido y el usuario pidió split,
            // se divide el PDF optimizado por rangos de páginas con Ghostscript
            // (mucho más eficiente que rasterizar cada página).
            // =================================================================
            $stage1Succeeded = false;
            $stage1Qualities = [70, 50, 35, 20];
            $bestStage1Size = PHP_INT_MAX;

            foreach ($stage1Qualities as $q1) {
                $result['compression_attempts']++;
                $s1Path = $tempDir . '/s1_q' . $q1 . '.pdf';

                if ($this->tryDirectGsOptimization($inputPath, $s1Path, $q1)) {
                    $s1Size = filesize($s1Path);
                    Log::info("VucemConverter: Stage 1 - calidad {$q1}%", [
                        'size_mb' => round($s1Size / 1048576, 2),
                    ]);

                    if ($s1Size < $bestStage1Size) {
                        if ($bestStage1Path && file_exists($bestStage1Path)) {
                            @unlink($bestStage1Path);
                        }
                        $bestStage1Size = $s1Size;
                        $bestStage1Path = $s1Path;
                    } else {
                        @unlink($s1Path);
                    }

                    // Si ya cabe en el límite VUCEM no necesitamos comprimir más
                    if ($s1Size <= $maxSize) {
                        break;
                    }
                } else {
                    if (file_exists($s1Path)) {
                        @unlink($s1Path);
                    }
                }
            }

            // Evaluar si Stage 1 produjo un resultado válido
            if ($bestStage1Path && $bestStage1Size < $originalSize) {
                // Stage 1 NO puede subir DPI: imágenes originales a < 300 DPI quedan igual.
                // Asumir no-cumple hasta verificar con pdfimages. Sin pdfimages no es seguro
                // usar Stage 1 porque VUCEM puede rechazar imágenes que no estén a 300 DPI exactos.
                $dpiCompliant = false;
                if ($this->pdfimagesPath) {
                    $dpiCheck = $this->validateDpi($bestStage1Path);
                    $totalDetected = $dpiCheck['total_images'] ?? 0;
                    if ($totalDetected === 0) {
                        $dpiCompliant = true;
                        Log::info('VucemConverter: Stage 1 sin imágenes rasterizadas, DPI OK (todo vectores)');
                    } elseif ($dpiCheck['valid'] ?? false) {
                        $dpiCompliant = true;
                        Log::info('VucemConverter: Stage 1 DPI validado correctamente', [
                            'total_images' => $totalDetected,
                        ]);
                    } else {
                        Log::info('VucemConverter: Stage 1 rechazado — imágenes no están a 300 DPI exactos, usando Stage 2', [
                            'invalid_count' => $dpiCheck['invalid_count'] ?? 0,
                            'total_images'  => $totalDetected,
                        ]);
                    }
                } else {
                    Log::info('VucemConverter: pdfimages no disponible, Stage 1 no verificable, usando Stage 2 (rasterización garantizada 300 DPI)');
                }

                if ($dpiCompliant) {
                    // ── División explícita pedida por el usuario ──────────────
                    if ($splitEnabled && $numberOfParts >= 2 && $numberOfParts <= 18) {
                        $s1TotalPages = $this->getPdfPageCount($bestStage1Path);
                        $partsInfo = $this->splitPdfByPageRangesGs($bestStage1Path, $outputPath, $numberOfParts, $s1TotalPages);

                        if (!empty($partsInfo)) {
                            $totalOut = array_sum(array_column($partsInfo, 'size'));
                            $sizeChange = round((($totalOut - $originalSize) / $originalSize) * 100, 2);
                            $result['converted_size'] = $totalOut;
                            $result['converted_size_mb'] = round($totalOut / 1048576, 2);
                            $result['size_change_percent'] = $sizeChange;
                            $result['was_reduced'] = $totalOut < $originalSize;
                            $result['final_quality'] = 'direct';
                            $result['total_pages'] = $s1TotalPages;
                            $result['parts'] = $partsInfo;
                            $result['success'] = true;
                            $result['messages'][] = "📊 Original: {$originalSizeMB} MB | Convertido: {$result['converted_size_mb']} MB (" .
                                ($sizeChange >= 0 ? '+' : '') . "{$sizeChange}%)";
                            Log::info('VucemConverter: Stage 1 con split por rangos completado', [
                                'parts'    => count($partsInfo),
                                'total_mb' => round($totalOut / 1048576, 2),
                            ]);
                            $stage1Succeeded = true;
                        }
                    }
                    // ── Sin división: verificar límite de tamaño ─────────────
                    elseif ($bestStage1Size <= $maxSize) {
                        copy($bestStage1Path, $outputPath);
                        $sizeChange = round((($bestStage1Size - $originalSize) / $originalSize) * 100, 2);
                        $result['converted_size'] = $bestStage1Size;
                        $result['converted_size_mb'] = round($bestStage1Size / 1048576, 2);
                        $result['size_change_percent'] = $sizeChange;
                        $result['was_reduced'] = true;
                        $result['final_quality'] = 'direct';
                        $result['success'] = true;
                        $result['messages'][] = "📊 Original: {$originalSizeMB} MB | Convertido: {$result['converted_size_mb']} MB (" .
                            ($sizeChange >= 0 ? '+' : '') . "{$sizeChange}%)";

                        if ($result['converted_size_mb'] >= ($warnSize / 1048576)) {
                            $result['exceeded_threshold'] = true;
                            $result['warnings'][] = "⚠️ Archivo cerca del límite ({$result['converted_size_mb']} MB / " .
                                round($maxSize / 1048576, 1) . " MB máximo)";
                        }

                        Log::info('VucemConverter: Stage 1 completado (sin rasterizar)', [
                            'size_mb'   => $result['converted_size_mb'],
                            'reduction' => $sizeChange . '%',
                        ]);
                        $stage1Succeeded = true;
                    }
                    // ── Sin división pero supera el límite → auto-dividir Stage 1 ──
                    elseif ($autoSplit) {
                        $s1TotalPages = $this->getPdfPageCount($bestStage1Path);
                        $partsNeeded = $this->calculateOptimalParts($s1TotalPages, $bestStage1Size, $maxSize);
                        $partsInfo = $this->splitPdfByPageRangesGs($bestStage1Path, $outputPath, $partsNeeded, $s1TotalPages);

                        if (!empty($partsInfo)) {
                            $totalOut = array_sum(array_column($partsInfo, 'size'));
                            $sizeChange = round((($totalOut - $originalSize) / $originalSize) * 100, 2);
                            $result['converted_size'] = $totalOut;
                            $result['converted_size_mb'] = round($totalOut / 1048576, 2);
                            $result['size_change_percent'] = $sizeChange;
                            $result['was_reduced'] = $totalOut < $originalSize;
                            $result['final_quality'] = 'direct';
                            $result['total_pages'] = $s1TotalPages;
                            $result['auto_divided'] = true;
                            $result['parts'] = $partsInfo;
                            $result['exceeded_threshold'] = true;
                            $result['success'] = true;
                            $result['warnings'][] = "⚠️ Excedió límite de " . round($maxSize / 1048576, 1) . " MB - Dividido automáticamente en {$partsNeeded} partes";
                            $result['messages'][] = "✂️ Archivo dividido automáticamente en {$partsNeeded} partes para cumplir con el límite de VUCEM";
                            $result['messages'][] = "📊 Original: {$originalSizeMB} MB | Convertido: {$result['converted_size_mb']} MB (" .
                                ($sizeChange >= 0 ? '+' : '') . "{$sizeChange}%)";
                            Log::info('VucemConverter: Stage 1 auto-dividido completado', [
                                'parts'    => count($partsInfo),
                                'total_mb' => round($totalOut / 1048576, 2),
                            ]);
                            $stage1Succeeded = true;
                        }
                    }
                }
            }

            if ($stage1Succeeded) {
                return $result;
            }

            // =================================================================
            // STAGE 2: Rasterización completa a exactamente 300 DPI
            // CORRECCIÓN del bug: cada intento de calidad regenera los JPEGs
            // con Ghostscript (rasterizeAtQuality), de modo que los archivos
            // resultantes reflejan realmente la calidad solicitada.
            // =================================================================
            $jpegPattern = $tempDir . '/page_%03d.jpg';
            $totalPages = 0;

            // División explícita + Stage 2 (rasterización): calidades progresivas
            if ($splitEnabled && $numberOfParts >= 2 && $numberOfParts <= 18) {
                $splitQualities = [15, 10, 8, 5];
                $bestSplitPartsInfo = null;
                $bestSplitTotalSize = PHP_INT_MAX;
                $bestSplitQuality = 15;

                foreach ($splitQualities as $quality) {
                    $result['compression_attempts']++;
                    $jpegFiles = $this->rasterizeAtQuality($inputPath, $jpegPattern, $quality);
                    $totalPages = count($jpegFiles);

                    if ($totalPages === 0) {
                        throw new RuntimeException('No se generaron páginas JPEG');
                    }

                    $partsInfo = $this->splitPdfIntoParts($jpegFiles, $outputPath, $numberOfParts, $forceOrientation, $tempDir);
                    $totalOut = array_sum(array_column($partsInfo, 'size'));
                    $maxPartSize = !empty($partsInfo) ? max(array_column($partsInfo, 'size')) : 0;

                    Log::info("VucemConverter: Stage 2 split - calidad {$quality}%", [
                        'total_mb'    => round($totalOut / 1048576, 2),
                        'max_part_mb' => round($maxPartSize / 1048576, 2),
                    ]);

                    if ($totalOut < $bestSplitTotalSize) {
                        $bestSplitTotalSize = $totalOut;
                        $bestSplitPartsInfo = $partsInfo;
                        $bestSplitQuality = $quality;
                    }

                    // Si todas las partes caben en el límite, no comprimir más
                    if ($maxPartSize <= $maxSize) {
                        break;
                    }
                }

                $result['total_pages'] = $totalPages;
                $result['parts'] = $bestSplitPartsInfo ?? [];
                $result['auto_divided'] = false;
                $result['final_quality'] = $bestSplitQuality;
                $result['success'] = true;
                $result['converted_size'] = $bestSplitTotalSize;
                $result['converted_size_mb'] = round($bestSplitTotalSize / 1048576, 2);
                $result['size_change_percent'] = round((($bestSplitTotalSize - $originalSize) / $originalSize) * 100, 2);
                $result['was_reduced'] = $bestSplitTotalSize < $originalSize;
                $result['messages'][] = "📊 Original: {$originalSizeMB} MB | Convertido: {$result['converted_size_mb']} MB (" .
                    ($result['size_change_percent'] >= 0 ? '+' : '') . "{$result['size_change_percent']}%)";

                return $result;
            }

            // Compresión progresiva con regeneración real de JPEGs por calidad
            $stage2Qualities = [50, 35, 20, 12, 8];
            $bestStage2Size = PHP_INT_MAX;
            $bestStage2Quality = 50;
            $jpegFiles = [];

            foreach ($stage2Qualities as $quality) {
                $result['compression_attempts']++;
                $jpegFiles = $this->rasterizeAtQuality($inputPath, $jpegPattern, $quality);
                $totalPages = count($jpegFiles);

                if ($totalPages === 0) {
                    throw new RuntimeException('No se generaron páginas JPEG');
                }

                $testOutput = $tempDir . '/s2_q' . $quality . '.pdf';
                $this->rebuildPdfFromJpegs($jpegFiles, $testOutput, 100, $forceOrientation, $tempDir);

                if (file_exists($testOutput)) {
                    $testSize = filesize($testOutput);
                    Log::info("VucemConverter: Stage 2 - calidad {$quality}%", [
                        'size_mb' => round($testSize / 1048576, 2),
                    ]);

                    if ($testSize < $bestStage2Size) {
                        $bestStage2Size = $testSize;
                        $bestStage2Quality = $quality;
                        copy($testOutput, $outputPath);
                    }
                    @unlink($testOutput);

                    // Cumple con el límite, no necesitamos más compresión
                    if ($testSize <= $maxSize) {
                        break;
                    }
                }
            }

            // Si el mejor resultado aún supera el límite, recargamos los JPEGs
            // a esa calidad para usarlos en la división automática
            if ($bestStage2Size > $maxSize) {
                $jpegFiles = $this->rasterizeAtQuality($inputPath, $jpegPattern, $bestStage2Quality);
                $totalPages = count($jpegFiles);
            }

            $result['total_pages'] = $totalPages;
            $result['converted_size'] = file_exists($outputPath) ? filesize($outputPath) : $bestStage2Size;
            $result['converted_size_mb'] = round($result['converted_size'] / 1048576, 2);
            $result['size_change_percent'] = round((($result['converted_size'] - $originalSize) / $originalSize) * 100, 2);
            $result['was_reduced'] = $result['converted_size'] < $originalSize;
            $result['final_quality'] = $bestStage2Quality;

            if (!$result['was_reduced']) {
                $result['warnings'][] = "Este documento contiene muchas imágenes pesadas. El archivo creció de {$originalSizeMB} MB a {$result['converted_size_mb']} MB.";
                $result['messages'][] = "Este documento contiene muchas imágenes pesadas que no se pueden comprimir más sin perder legibilidad.";
            }

            $result['messages'][] = "📊 Original: {$originalSizeMB} MB | Convertido: {$result['converted_size_mb']} MB (" .
                ($result['size_change_percent'] >= 0 ? '+' : '') . "{$result['size_change_percent']}%)";

            if ($result['converted_size_mb'] >= ($warnSize / 1048576)) {
                $result['exceeded_threshold'] = true;
                $result['warnings'][] = "⚠️ Archivo cerca del límite ({$result['converted_size_mb']} MB / " .
                    round($maxSize / 1048576, 1) . " MB máximo)";
            }

            if ($result['converted_size'] > $maxSize) {
                $result['exceeded_threshold'] = true;

                if ($autoSplit || $splitEnabled) {
                    $partsNeeded = $this->calculateOptimalParts($totalPages, $result['converted_size'], $maxSize);

                    Log::info("VucemConverter: Dividiendo automáticamente en {$partsNeeded} partes", [
                        'converted_size_mb' => $result['converted_size_mb'],
                        'max_size_mb' => round($maxSize / 1048576, 2),
                    ]);

                    $result['auto_divided'] = true;
                    $result['warnings'][] = "⚠️ Excedió límite de " . round($maxSize / 1048576, 1) . " MB - Dividido automáticamente en {$partsNeeded} partes";
                    $result['messages'][] = "✂️ Archivo dividido automáticamente en {$partsNeeded} partes para cumplir con el límite de VUCEM";

                    $partsInfo = $this->splitPdfIntoParts($jpegFiles, $outputPath, $partsNeeded, $forceOrientation, $tempDir);
                    $result['parts'] = $partsInfo;
                    $result['success'] = true;

                    return $result;
                } else {
                    $result['warnings'][] = "⚠️ Archivo excede el límite de " . round($maxSize / 1048576, 1) . " MB. Activa la división automática o usa la opción manual.";
                    $result['messages'][] = "💡 Para dividir manualmente, activa la opción 'Dividir PDF en partes'.";
                }
            }

            if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
                throw new RuntimeException('No se pudo crear el PDF');
            }

            $result['success'] = true;

        } catch (\Exception $e) {
            Log::error('VucemConverter: Error en conversión optimizada', [
                'error' => $e->getMessage(),
            ]);
            $result['error'] = $e->getMessage();
            throw $e;
        } finally {
            if ($bestStage1Path && file_exists($bestStage1Path)) {
                @unlink($bestStage1Path);
            }
            $this->cleanupDirectory($tempDir);
        }

        return $result;
    }

    /**
     * Reconstruye PDF desde JPEGs usando Ghostscript + PostScript (sin TCPDF).
     * Genera un documento PS que embebe cada JPEG con el tamaño de página exacto
     * calculado a 300 DPI, luego lo convierte a PDF 1.4 con Ghostscript.
     */
    protected function rebuildPdfFromJpegs(array $jpegFiles, string $outputPath, int $quality, string $forceOrientation, string $tempDir): bool
    {
        $psLines = [
            '%!PS-Adobe-3.0',
            '%%Pages: ' . count($jpegFiles),
            '%%EndComments',
        ];

        $pageNum = 1;
        foreach ($jpegFiles as $jpegFile) {
            [$widthPx, $heightPx] = getimagesize($jpegFile);
            $widthPt  = round(($widthPx / 300) * 72, 4);
            $heightPt = round(($heightPx / 300) * 72, 4);

            // Forward slashes work on both Linux and Ghostscript on Windows
            $safePath = str_replace('\\', '/', $jpegFile);

            $psLines[] = "%%Page: {$pageNum} {$pageNum}";
            $psLines[] = "<< /PageSize [{$widthPt} {$heightPt}] >> setpagedevice";
            $psLines[] = "{$widthPt} {$heightPt} scale";
            $psLines[] = '/DeviceGray setcolorspace';
            $psLines[] = '<<';
            $psLines[] = '  /ImageType 1';
            $psLines[] = "  /Width {$widthPx}";
            $psLines[] = "  /Height {$heightPx}";
            $psLines[] = '  /BitsPerComponent 8';
            $psLines[] = '  /Decode [0 1]';
            $psLines[] = "  /ImageMatrix [{$widthPx} 0 0 -{$heightPx} 0 {$heightPx}]";
            $psLines[] = "  /DataSource ({$safePath}) (r) file /DCTDecode filter";
            $psLines[] = '>> image';
            $psLines[] = 'showpage';
            $pageNum++;
        }
        $psLines[] = '%%EOF';

        $psPath = $tempDir . '/assembled_' . uniqid() . '.ps';
        file_put_contents($psPath, implode("\n", $psLines) . "\n");

        try {
            $this->executeGhostscript([
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-sOutputFile=' . $outputPath,
                $psPath,
            ]);
        } finally {
            if (file_exists($psPath)) {
                @unlink($psPath);
            }
        }

        return file_exists($outputPath) && filesize($outputPath) > 1000;
    }

    /**
     * Optimización directa PDF→PDF con Ghostscript sin rasterizar.
     *
     * Preserva texto y vectores tal cual (muy compactos). Solo procesa imágenes:
     * - Convierte imágenes de color a escala de grises.
     * - Reduce las que superan 300 DPI a exactamente 300 DPI (Bicubic downsample).
     * - Comprime con JPEG a la calidad indicada.
     *
     * Ventaja: PDFs con contenido vectorial/texto quedan MUCHO más pequeños
     * que con rasterización total, ya que el texto no se convierte en píxeles.
     *
     * Limitación: imágenes originales con menos de 300 DPI NO son upsampled.
     * Si VUCEM requiere exactamente 300 DPI en todas las imágenes, Stage 2
     * (rasterización) será necesario.
     */
    protected function tryDirectGsOptimization(string $inputPath, string $outputPath, int $jpegQuality): bool
    {
        $this->executeGhostscript([
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-sColorConversionStrategy=Gray',
            '-dProcessColorModel=/DeviceGray',
            // Reducir imágenes con >300 DPI a exactamente 300 DPI
            '-dDownsampleGrayImages=true',
            '-dGrayImageDownsampleType=/Bicubic',
            '-dGrayImageResolution=300',
            '-dGrayImageDownsampleThreshold=1.0',
            '-dDownsampleColorImages=true',
            '-dColorImageDownsampleType=/Bicubic',
            '-dColorImageResolution=300',
            '-dColorImageDownsampleThreshold=1.0',
            '-dDownsampleMonoImages=false',
            // Comprimir imágenes con JPEG a la calidad solicitada
            '-dAutoFilterGrayImages=false',
            '-dGrayImageFilter=/DCTEncode',
            '-dEncodeGrayImages=true',
            '-dAutoFilterColorImages=false',
            '-dColorImageFilter=/DCTEncode',
            '-dEncodeColorImages=true',
            '-dJPEGQ=' . $jpegQuality,
            // Optimizar fuentes y estructura del PDF
            '-dCompressFonts=true',
            '-dOptimize=true',
            '-sOutputFile=' . $outputPath,
            $inputPath,
        ]);

        return file_exists($outputPath) && filesize($outputPath) > 1000;
    }

    /**
     * Rasteriza todas las páginas del PDF a archivos JPEG independientes.
     *
     * CORRECCIÓN del bug anterior: este método regenera los JPEGs llamando a
     * Ghostscript con la calidad indicada, de modo que el tamaño resultante
     * refleja de verdad esa calidad. Antes, el loop solo cambiaba el parámetro
     * de TCPDF (setJPEGQuality), que no tiene efecto sobre archivos .jpg ya
     * existentes generados por GS — todos los intentos producían el mismo tamaño.
     */
    protected function rasterizeAtQuality(string $inputPath, string $jpegPattern, int $quality): array
    {
        // Eliminar JPEGs previos del directorio para evitar mezclas de calidad
        $dir = dirname($jpegPattern);
        foreach (glob($dir . '/page_*.jpg') ?: [] as $old) {
            @unlink($old);
        }

        $this->executeGhostscript([
            '-sDEVICE=jpeggray',
            '-r300',
            '-dJPEGQ=' . $quality,
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dQUIET',
            '-sOutputFile=' . $jpegPattern,
            $inputPath,
        ]);

        $jpegFiles = glob($dir . '/page_*.jpg') ?: [];
        sort($jpegFiles, SORT_NATURAL);
        return $jpegFiles;
    }

    /**
     * Calcula el número óptimo de partes para dividir un PDF
     */
    protected function calculateOptimalParts(int $totalPages, int $outputSize, int $maxSize): int
    {
        if ($outputSize <= $maxSize) {
            return 1;
        }

        $ratio = $outputSize / $maxSize;
        $estimatedParts = (int) ceil($ratio);
        
        $maxPagesPerPart = $this->getConfig('max_pages_per_part', 10);
        $pagesPerPart = (int) ceil($totalPages / $estimatedParts);
        
        if ($pagesPerPart > $maxPagesPerPart) {
            $pagesPerPart = $maxPagesPerPart;
            $estimatedParts = (int) ceil($totalPages / $pagesPerPart);
        }

        return max(2, $estimatedParts);
    }

    /**
     * Cuenta las páginas de un PDF usando Ghostscript
     */
    protected function getPdfPageCount(string $pdfPath): int
    {
        $result = $this->executeGhostscript([
            '-dQUIET',
            '-dNODISPLAY',
            '-dNOSAFER',
            '-dNOPAUSE',
            '-dBATCH',
            '-c',
            "({$pdfPath}) (r) file runpdfbegin pdfpagecount = quit",
        ]);
        return max(1, intval(trim($result['output'])));
    }

    /**
     * Divide un PDF en N partes por rangos de páginas usando Ghostscript.
     * No rasteriza: extrae rangos directamente del PDF optimizado (Stage 1).
     * Mucho más eficiente que rasterizar + reconstruir para PDFs con vectores.
     */
    protected function splitPdfByPageRangesGs(string $inputPath, string $outputPath, int $numParts, int $totalPages): array
    {
        $pagesPerPart = (int) ceil($totalPages / $numParts);
        $partsInfo = [];

        for ($i = 0; $i < $numParts; $i++) {
            $firstPage = $i * $pagesPerPart + 1;
            $lastPage  = min(($i + 1) * $pagesPerPart, $totalPages);

            if ($firstPage > $totalPages) {
                break;
            }

            $groupNumber = $i + 1;
            $groupOutput = str_replace('.pdf', "_parte{$groupNumber}.pdf", $outputPath);

            $this->executeGhostscript([
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                '-dQUIET',
                '-dFirstPage=' . $firstPage,
                '-dLastPage='  . $lastPage,
                '-sOutputFile=' . $groupOutput,
                $inputPath,
            ]);

            if (file_exists($groupOutput) && filesize($groupOutput) > 100) {
                $groupSize = filesize($groupOutput);
                $partsInfo[] = [
                    'path'    => $groupOutput,
                    'part'    => $groupNumber,
                    'pages'   => $lastPage - $firstPage + 1,
                    'size'    => $groupSize,
                    'size_mb' => round($groupSize / 1048576, 2),
                ];
            }
        }

        return $partsInfo;
    }

    /**
     * Divide el PDF en partes óptimas
     */
    protected function splitPdfIntoParts(array $jpegFiles, string $outputPath, int $numParts, string $forceOrientation, string $tempDir): array
    {
        $partsInfo = [];
        $pagesPerPart = ceil(count($jpegFiles) / $numParts);
        $groups = array_chunk($jpegFiles, $pagesPerPart);

        foreach ($groups as $groupIndex => $groupJpegs) {
            $groupNumber = $groupIndex + 1;
            $groupOutput = str_replace('.pdf', "_parte{$groupNumber}.pdf", $outputPath);

            $this->rebuildPdfFromJpegs($groupJpegs, $groupOutput, 15, $forceOrientation, $tempDir);

            if (file_exists($groupOutput)) {
                $groupSize = filesize($groupOutput);
                $partsInfo[] = [
                    'path' => $groupOutput,
                    'part' => $groupNumber,
                    'pages' => count($groupJpegs),
                    'size' => $groupSize,
                    'size_mb' => round($groupSize / (1024 * 1024), 2),
                ];
            }
        }

        return $partsInfo;
    }
}

