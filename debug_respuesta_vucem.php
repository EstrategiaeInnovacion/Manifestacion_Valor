<?php

/**
 * Script para analizar la respuesta completa de VUCEM
 * y detectar dónde están los archivos/acuses
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=================================================================\n";
echo "   DEBUG RESPUESTA COMPLETA DE VUCEM\n";
echo "=================================================================\n\n";

// Configuración
$folio = '043825195EDK2'; // El folio de la captura de pantalla
$rfc = 'NET070608EM9';
$claveWebService = 'netico2024';

// Rutas de archivos eFirma (ajustar según tu configuración)
$rutaCertificado = __DIR__ . '/pruebaEfirma/00001000000716248795.cer';
$rutaLlave = __DIR__ . '/pruebaEfirma/Claveprivada_FIEL_NET070608EM9_20250604_163343.key';
$passwordLlave = file_get_contents(__DIR__ . '/pruebaEfirma/CONTRASEÑA.txt');

echo "📋 Parámetros de consulta:\n";
echo "   - Folio: $folio\n";
echo "   - RFC: $rfc\n";
echo "   - Clave WS: " . str_repeat('*', strlen($claveWebService)) . "\n\n";

if (!file_exists($rutaCertificado)) {
    echo "❌ No se encuentra el certificado: $rutaCertificado\n";
    exit(1);
}

if (!file_exists($rutaLlave)) {
    echo "❌ No se encuentra la llave privada: $rutaLlave\n";
    exit(1);
}

try {
    echo "🔄 Iniciando consulta a VUCEM...\n\n";
    
    $service = app(\App\Services\ConsultarEdocumentService::class);
    
    $resultado = $service->consultarEdocument(
        $folio,
        $rfc,
        $claveWebService,
        $rutaCertificado,
        $rutaLlave,
        trim($passwordLlave)
    );
    
    echo "=================================================================\n";
    echo "   RESULTADO DE LA CONSULTA\n";
    echo "=================================================================\n\n";
    
    echo "✅ Éxito: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
    echo "📝 Mensaje: " . ($resultado['message'] ?? 'Sin mensaje') . "\n\n";
    
    if (isset($resultado['cove_data'])) {
        echo "=================================================================\n";
        echo "   DATOS DEL COVE\n";
        echo "=================================================================\n\n";
        print_r($resultado['cove_data']);
        echo "\n";
    }
    
    if (isset($resultado['archivos']) && !empty($resultado['archivos'])) {
        echo "=================================================================\n";
        echo "   ARCHIVOS ENCONTRADOS: " . count($resultado['archivos']) . "\n";
        echo "=================================================================\n\n";
        
        foreach ($resultado['archivos'] as $index => $archivo) {
            echo "Archivo #" . ($index + 1) . ":\n";
            echo "   - Nombre: " . ($archivo['nombre'] ?? 'Sin nombre') . "\n";
            echo "   - Tipo: " . ($archivo['tipo'] ?? 'Sin tipo') . "\n";
            echo "   - Tamaño: " . (isset($archivo['tamano']) ? number_format($archivo['tamano']) . ' bytes' : 'Desconocido') . "\n";
            if (isset($archivo['descripcion'])) {
                echo "   - Descripción: " . $archivo['descripcion'] . "\n";
            }
            if (isset($archivo['contenido'])) {
                $contenidoLength = strlen($archivo['contenido']);
                echo "   - Contenido: " . number_format($contenidoLength) . " caracteres\n";
                echo "   - Primeros 50 caracteres: " . substr($archivo['contenido'], 0, 50) . "...\n";
            }
            echo "\n";
        }
    } else {
        echo "⚠️  No se encontraron archivos en la respuesta\n\n";
    }
    
    echo "=================================================================\n";
    echo "   INFORMACIÓN DE DEBUG SOAP\n";
    echo "=================================================================\n\n";
    
    $debugInfo = $service->getDebugInfo();
    
    if (!empty($debugInfo['last_request'])) {
        echo "📤 SOAP REQUEST:\n";
        echo str_repeat('-', 65) . "\n";
        // Formatear XML
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        @$dom->loadXML($debugInfo['last_request']);
        echo $dom->saveXML();
        echo "\n";
    }
    
    if (!empty($debugInfo['last_response'])) {
        echo "📥 SOAP RESPONSE (COMPLETA):\n";
        echo str_repeat('-', 65) . "\n";
        // Formatear XML
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        @$dom->loadXML($debugInfo['last_response']);
        $formattedXml = $dom->saveXML();
        echo $formattedXml;
        echo "\n";
        
        // Guardar en archivo para análisis
        $archivoRespuesta = __DIR__ . '/ultima_respuesta_vucem.xml';
        file_put_contents($archivoRespuesta, $formattedXml);
        echo "💾 Respuesta guardada en: $archivoRespuesta\n\n";
    }
    
    echo "=================================================================\n";
    echo "   ANÁLISIS DE LA ESTRUCTURA XML\n";
    echo "=================================================================\n\n";
    
    if (!empty($debugInfo['last_response'])) {
        $xml = simplexml_load_string($debugInfo['last_response']);
        $namespaces = $xml->getNamespaces(true);
        
        echo "Namespaces encontrados:\n";
        foreach ($namespaces as $prefix => $uri) {
            echo "   - " . ($prefix ?: '[default]') . " => $uri\n";
        }
        echo "\n";
        
        echo "Buscando campos relacionados con archivos...\n";
        $camposArchivo = ['archivo', 'adjunto', 'acuse', 'documento', 'file', 'attachment'];
        foreach ($camposArchivo as $campo) {
            $xpath = "//*[contains(local-name(), '$campo')]";
            $resultado = $xml->xpath($xpath);
            if (!empty($resultado)) {
                echo "   ✓ Encontrados " . count($resultado) . " elementos con '$campo'\n";
            }
        }
    }
    
    echo "\n✅ Análisis completado\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
