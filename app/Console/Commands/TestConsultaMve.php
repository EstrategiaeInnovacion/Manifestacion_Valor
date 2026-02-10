<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MveConsultaService;

class TestConsultaMve extends Command
{
    protected $signature = 'mve:test-consulta {folio} {rfc} {clave}';
    protected $description = 'Test de consulta MVE a VUCEM - Muestra toda la información recibida';

    public function handle()
    {
        $folio = $this->argument('folio');
        $rfc = $this->argument('rfc');
        $clave = $this->argument('clave');

        $this->info("╔══════════════════════════════════════════════════════════════════╗");
        $this->info("║           TEST DE CONSULTA MVE A VUCEM                          ║");
        $this->info("╠══════════════════════════════════════════════════════════════════╣");
        $this->info("║ Folio: {$folio}");
        $this->info("║ RFC: {$rfc}");
        $this->info("╚══════════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $service = new MveConsultaService();
        
        $this->info("🔄 Consultando a VUCEM...");
        $this->newLine();

        $resultado = $service->consultarManifestacion($folio, $rfc, $clave);

        // Mostrar resultado general
        $this->info("═══════════════════════════════════════════════════════════════════");
        $this->info(" RESULTADO GENERAL");
        $this->info("═══════════════════════════════════════════════════════════════════");
        
        if ($resultado['success']) {
            $this->info("✅ Status: ÉXITO");
        } else {
            $this->error("❌ Status: ERROR");
        }
        
        $this->line("📝 Mensaje: " . ($resultado['message'] ?? 'N/A'));
        $this->line("📋 Número MV: " . ($resultado['numero_mv'] ?? 'N/A'));
        $this->line("📊 Estado: " . ($resultado['status'] ?? 'N/A'));
        $this->line("📅 Fecha Registro: " . ($resultado['fecha_registro'] ?? 'N/A'));
        $this->line("📄 Acuse PDF: " . (!empty($resultado['acuse_pdf']) ? 'SÍ (' . strlen($resultado['acuse_pdf']) . ' bytes)' : 'NO'));
        $this->newLine();

        // Mostrar errores si los hay
        if (!empty($resultado['errores'])) {
            $this->error("═══════════════════════════════════════════════════════════════════");
            $this->error(" ERRORES DE VUCEM");
            $this->error("═══════════════════════════════════════════════════════════════════");
            foreach ($resultado['errores'] as $error) {
                $this->error("  [{$error['codigo']}] {$error['descripcion']}");
            }
            $this->newLine();
        }

        // Mostrar datos de manifestación
        if (!empty($resultado['datos_manifestacion'])) {
            $this->info("═══════════════════════════════════════════════════════════════════");
            $this->info(" DATOS DE MANIFESTACIÓN EXTRAÍDOS");
            $this->info("═══════════════════════════════════════════════════════════════════");
            
            $this->mostrarDatosRecursivo($resultado['datos_manifestacion'], 0);
            $this->newLine();
        } else {
            $this->warn("⚠️  No se extrajeron datos de manifestación");
            $this->newLine();
        }

        // Mostrar XML enviado
        $this->info("═══════════════════════════════════════════════════════════════════");
        $this->info(" XML ENVIADO A VUCEM");
        $this->info("═══════════════════════════════════════════════════════════════════");
        if (!empty($resultado['xml_sent'])) {
            $this->line($this->formatXml($resultado['xml_sent']));
        }
        $this->newLine();

        // Mostrar respuesta cruda de VUCEM
        $this->info("═══════════════════════════════════════════════════════════════════");
        $this->info(" RESPUESTA CRUDA DE VUCEM (XML COMPLETO)");
        $this->info("═══════════════════════════════════════════════════════════════════");
        if (!empty($resultado['response'])) {
            $this->line($this->formatXml($resultado['response']));
        }
        $this->newLine();

        // Guardar respuesta en archivo para análisis
        $filename = storage_path('logs/vucem_response_' . $folio . '_' . date('YmdHis') . '.xml');
        if (!empty($resultado['response'])) {
            file_put_contents($filename, $resultado['response']);
            $this->info("💾 Respuesta XML guardada en: {$filename}");
        }

        // Guardar datos parseados en JSON
        $jsonFile = storage_path('logs/vucem_datos_' . $folio . '_' . date('YmdHis') . '.json');
        file_put_contents($jsonFile, json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("💾 Datos JSON guardados en: {$jsonFile}");

        return 0;
    }

    private function mostrarDatosRecursivo($datos, $nivel)
    {
        $indent = str_repeat("  ", $nivel);
        
        foreach ($datos as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssociativeArray($value)) {
                    $this->line("{$indent}📁 {$key}:");
                    $this->mostrarDatosRecursivo($value, $nivel + 1);
                } else {
                    $this->line("{$indent}📋 {$key}: [" . count($value) . " elementos]");
                    foreach ($value as $i => $item) {
                        if (is_array($item)) {
                            $this->line("{$indent}  [{$i}]:");
                            $this->mostrarDatosRecursivo($item, $nivel + 2);
                        } else {
                            $this->line("{$indent}  [{$i}] => {$item}");
                        }
                    }
                }
            } else {
                $displayValue = $value;
                if (strlen($displayValue) > 100) {
                    $displayValue = substr($displayValue, 0, 100) . '... (' . strlen($value) . ' chars)';
                }
                $this->line("{$indent}• {$key}: {$displayValue}");
            }
        }
    }

    private function isAssociativeArray($arr)
    {
        if (!is_array($arr) || empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function formatXml($xml)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        
        // Suprimir errores de XML mal formado
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();
        
        $formatted = $dom->saveXML();
        return $formatted ?: $xml;
    }
}
