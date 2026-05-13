<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VucemPdfConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PdfDebugController extends Controller
{
    protected VucemPdfConverter $converter;

    public function __construct(VucemPdfConverter $converter)
    {
        $this->converter = $converter;
    }

    public function index()
    {
        $toolsInfo  = $this->converter->getToolsInfo();
        $debugInfo  = $this->converter->getDebugInfo();

        return view('admin.pdf-debug', compact('toolsInfo', 'debugInfo'));
    }

    public function test(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file     = $request->file('archivo');
        $tempIn   = $file->getRealPath();
        $tempOut  = tempnam(sys_get_temp_dir(), 'vucem_debug_') . '.pdf';

        $before = $this->analyzeFile($tempIn);

        $conversionResult = [];
        $convertedPath    = null;
        $after            = null;
        $error            = null;

        try {
            $conversionResult = $this->converter->convertToVucemOptimized($tempIn, $tempOut);
            $convertedPath    = $tempOut;

            if (file_exists($tempOut) && filesize($tempOut) > 100) {
                $after = $this->analyzeFile($tempOut);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('[PDF_DEBUG] Error en conversión de prueba: ' . $e->getMessage());
        } finally {
            if ($convertedPath && file_exists($convertedPath)) {
                @unlink($convertedPath);
            }
        }

        $toolsInfo = $this->converter->getToolsInfo();

        return view('admin.pdf-debug', compact(
            'toolsInfo',
            'before',
            'after',
            'conversionResult',
            'error'
        ))->with('debugInfo', $this->converter->getDebugInfo());
    }

    /**
     * Lee los metadatos básicos de un PDF directamente del archivo
     */
    protected function analyzeFile(string $path): array
    {
        $size   = filesize($path);
        $handle = fopen($path, 'rb');
        $header = $handle ? fread($handle, 1024) : '';
        if ($handle) fclose($handle);

        $version = null;
        if (preg_match('/%PDF-(\d+\.\d+)/', $header, $m)) {
            $version = $m[1];
        }

        // Verificar encriptación
        $encrypted = str_contains($header, '/Encrypt');

        // DPI via pdfimages (si está disponible)
        $dpiInfo = $this->converter->validateDpi($path);

        return [
            'size_bytes' => $size,
            'size_mb'    => round($size / (1024 * 1024), 3),
            'version'    => $version,
            'encrypted'  => $encrypted,
            'dpi'        => $dpiInfo,
        ];
    }
}
