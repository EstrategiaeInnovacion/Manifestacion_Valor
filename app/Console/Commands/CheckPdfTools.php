<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CheckPdfTools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:check-tools';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y configura las herramientas PDF necesarias para VUCEM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando herramientas PDF para VUCEM...');
        $this->line('');
        
        // Verificar Ghostscript
        $this->checkGhostscript();
        $this->line('');
        
        // Verificar PDFImages (opcional)
        $this->checkPdfImages();
        $this->line('');
        
        // Mostrar configuración actual
        $this->showCurrentConfig();
        $this->line('');
        
        // Probar conversión básica
        $this->testBasicConversion();
        
        return Command::SUCCESS;
    }
    
    protected function checkGhostscript()
    {
        $this->info('📄 Ghostscript:');
        
        $configPath = config('pdftools.ghostscript');
        if (!empty($configPath)) {
            $this->line("   Configurado: {$configPath}");
            
            if ($this->testGhostscriptPath($configPath)) {
                $this->line('   ✅ <fg=green>Funcionando correctamente</fg=green>');
                return;
            } else {
                $this->line('   ❌ <fg=red>Ruta configurada no funciona</fg=red>');
            }
        } else {
            $this->line('   📝 No configurado - buscando automáticamente...');
        }
        
        // Autodetectar
        $foundPath = $this->findGhostscript();
        if ($foundPath) {
            $this->line("   🔍 <fg=yellow>Encontrado: {$foundPath}</fg=yellow>");
            $this->line('   💡 Agrega esta línea a tu .env:');
            $this->line("   <fg=cyan>GHOSTSCRIPT_PATH=\"{$foundPath}\"</fg=cyan>");
        } else {
            $this->line('   ❌ <fg=red>No encontrado</fg=red>');
            $this->showGhostscriptInstallInstructions();
        }
    }
    
    protected function checkPdfImages()
    {
        $this->info('🖼️  PDFImages (Poppler Utils):');
        
        $configPath = config('pdftools.pdfimages');
        if (!empty($configPath)) {
            $this->line("   Configurado: {$configPath}");
            
            if ($this->testPdfImagesPath($configPath)) {
                $this->line('   ✅ <fg=green>Funcionando correctamente</fg=green>');
                return;
            } else {
                $this->line('   ❌ <fg=red>Ruta configurada no funciona</fg=red>');
            }
        } else {
            $this->line('   📝 No configurado - buscando automáticamente...');
        }
        
        // Autodetectar
        $foundPath = $this->findPdfImages();
        if ($foundPath) {
            $this->line("   🔍 <fg=yellow>Encontrado: {$foundPath}</fg=yellow>");
            $this->line('   💡 Agrega esta línea a tu .env:');
            $this->line("   <fg=cyan>PDFIMAGES_PATH=\"{$foundPath}\"</fg=cyan>");
        } else {
            $this->line('   ❌ <fg=red>No encontrado</fg=red>');
            $this->line('   ℹ️  <fg=blue>PDFImages es opcional - solo mejora la detección de calidad</fg=blue>');
        }
    }
    
    protected function showCurrentConfig()
    {
        $this->info('⚙️  Configuración actual:');
        
        $configs = [
            'Tamaño máximo entrada' => config('pdftools.max_size_mb', 50) . ' MB',
            'Tamaño máximo VUCEM' => config('pdftools.vucem.max_final_size_mb', 3) . ' MB', 
            'Versión PDF requerida' => config('pdftools.vucem.required_version', '1.4'),
            'DPI de salida' => config('pdftools.vucem.required_dpi', 300) . ' DPI',
            'Forzar escala de grises' => config('pdftools.vucem.force_grayscale', true) ? 'Sí' : 'No',
            'Remover encriptación' => config('pdftools.vucem.remove_encryption', true) ? 'Sí' : 'No',
        ];
        
        foreach ($configs as $key => $value) {
            $this->line("   {$key}: <fg=yellow>{$value}</fg=yellow>");
        }
    }
    
    protected function testBasicConversion()
    {
        $this->info('🧪 Prueba básica de conversión:');
        
        $gsPath = config('pdftools.ghostscript') ?: $this->findGhostscript();
        
        if (!$gsPath) {
            $this->line('   ❌ <fg=red>No se puede probar - Ghostscript no disponible</fg=red>');
            return;
        }
        
        try {
            // Probar comando básico de Ghostscript
            $process = new Process([
                $gsPath,
                '-dNODISPLAY',
                '-dQUIET',
                '-c',
                '(Hello from Ghostscript) = quit'
            ]);
            
            $process->setTimeout(10);
            $process->run();
            
            if ($process->isSuccessful()) {
                $this->line('   ✅ <fg=green>Ghostscript responde correctamente</fg=green>');
            } else {
                $this->line('   ❌ <fg=red>Error en Ghostscript:</fg=red>');
                $this->line('   ' . $process->getErrorOutput());
            }
            
        } catch (\Exception $e) {
            $this->line('   ❌ <fg=red>Error probando Ghostscript: ' . $e->getMessage() . '</fg=red>');
        }
    }
    
    protected function testGhostscriptPath(string $path): bool
    {
        try {
            $process = new Process([$path, '--version']);
            $process->setTimeout(5);
            $process->run();
            
            return $process->isSuccessful();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function testPdfImagesPath(string $path): bool
    {
        try {
            $process = new Process([$path, '-v']);
            $process->setTimeout(5);
            $process->run();
            
            return $process->isSuccessful() || str_contains($process->getErrorOutput(), 'pdfimages');
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function findGhostscript(): ?string
    {
        $candidates = [];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $candidates = [
                'gswin64c.exe',
                'gswin32c.exe', 
                'gs.exe',
            ];
            
            // Buscar en ubicaciones comunes de Windows
            $commonPaths = [
                'C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs*\\bin\\gswin32c.exe',
            ];
            
            foreach ($commonPaths as $pattern) {
                $matches = glob($pattern);
                if (!empty($matches)) {
                    $candidates[] = $matches[0];
                }
            }
        } else {
            // Linux/Mac
            $candidates = ['gs', 'ghostscript'];
        }
        
        foreach ($candidates as $candidate) {
            if ($this->testGhostscriptPath($candidate)) {
                return $candidate;
            }
        }
        
        return null;
    }
    
    protected function findPdfImages(): ?string
    {
        $candidates = ['pdfimages'];
        
        foreach ($candidates as $candidate) {
            if ($this->testPdfImagesPath($candidate)) {
                return $candidate;
            }
        }
        
        return null;
    }
    
    protected function showGhostscriptInstallInstructions()
    {
        $this->line('');
        $this->line('   📋 <fg=blue>Instrucciones de instalación de Ghostscript:</fg=blue>');
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->line('   💻 <fg=cyan>Windows:</fg=cyan>');
            $this->line('      1. Descargar desde: https://www.ghostscript.com/download/gsdnld.html');
            $this->line('      2. Instalar la versión para Windows (64-bit recomendado)');
            $this->line('      3. Agregar a PATH o configurar ruta completa en .env');
        } else {
            $this->line('   🐧 <fg=cyan>Linux (Ubuntu/Debian):</fg=cyan>');
            $this->line('      sudo apt-get install ghostscript');
            $this->line('');
            $this->line('   🍎 <fg=cyan>macOS:</fg=cyan>');
            $this->line('      brew install ghostscript');
        }
    }
}
