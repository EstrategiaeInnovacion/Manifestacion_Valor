<?php

/**
 * Diagnóstico específico para el error "Could not connect to host"
 */

echo "=== DIAGNÓSTICO ERROR 'Could not connect to host' ===\n\n";

$wsdlUrl = 'https://privados.ventanillaunica.gob.mx:8106/IngresoManifestacionImpl/IngresoManifestacionService';
$endpointTest = 'https://www.ventanillaunica.gob.mx:8118/ventanilla/ConsultarEdocumentService';

echo "🔍 Analizando problemas de conectividad...\n\n";

try {
    echo "📍 1. Test básico de conectividad HTTP/HTTPS:\n";
    
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ],
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Laravel-VUCEM-Client/1.0'
        ]
    ]);
    
    // Test de conectividad a diferentes URLs
    $testUrls = [
        'WSDL URL' => $wsdlUrl,
        'Endpoint directo (puerto 8118)' => $endpointTest,
        'Host base' => 'https://www.ventanillaunica.gob.mx',
        'Endpoint original' => 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEdocumentService'
    ];
    
    foreach ($testUrls as $name => $url) {
        echo "   🔄 Testing {$name}...\n";
        echo "      URL: {$url}\n";
        
        $startTime = microtime(true);
        $content = @file_get_contents($url, false, $context);
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        if ($content !== false) {
            echo "      ✅ Conectividad OK - {$responseTime}ms - " . strlen($content) . " bytes\n";
        } else {
            echo "      ❌ ERROR - No se pudo conectar\n";
            $error = error_get_last();
            echo "      Error: " . ($error['message'] ?? 'Desconocido') . "\n";
        }
        echo "\n";
    }
    
    echo "📍 2. Test avanzado de SoapClient:\n";
    
    // Test con configuración similar a la del servicio
    $soapOptions = [
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'soap_version' => SOAP_1_1,
        'connection_timeout' => 30,
        'user_agent' => 'Laravel-VUCEM-Client/1.0',
        'stream_context' => $context
    ];
    
    echo "   🔄 Inicializando SoapClient con timeout extendido...\n";
    
    try {
        $soapClient = new SoapClient($wsdlUrl, $soapOptions);
        echo "   ✅ SoapClient inicializado correctamente\n";
        
        // Verificar endpoint que usa el cliente
        echo "   🔄 Obteniendo endpoint del WSDL...\n";
        $wsdlContent = file_get_contents($wsdlUrl, false, $context);
        
        if (preg_match('/<soap:address location="([^"]+)"/', $wsdlContent, $matches)) {
            $realEndpoint = $matches[1];
            echo "   📍 Endpoint real desde WSDL: {$realEndpoint}\n";
            
            // Test de conectividad al endpoint real
            echo "   🔄 Testing conectividad al endpoint real...\n";
            $endpointTest = @file_get_contents($realEndpoint, false, $context);
            if ($endpointTest !== false) {
                echo "   ✅ Endpoint real accesible\n";
            } else {
                echo "   ❌ Endpoint real NO accesible\n";
                $error = error_get_last();
                echo "   Error: " . ($error['message'] ?? 'Desconocido') . "\n";
            }
        }
        
    } catch (SoapFault $e) {
        echo "   ❌ Error SOAP: " . $e->getMessage() . "\n";
        echo "   Código: " . $e->getCode() . "\n";
        if (strpos($e->getMessage(), 'Could not connect to host') !== false) {
            echo "   🎯 ESTE ES EL ERROR QUE ESTÁS VIENDO!\n";
        }
    }
    
    echo "\n📍 3. Test de configuración PHP/OpenSSL:\n";
    
    echo "   📋 Configuración PHP:\n";
    echo "      - allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Habilitado ✅' : 'Deshabilitado ❌') . "\n";
    echo "      - user_agent: " . ini_get('user_agent') . "\n";
    echo "      - default_socket_timeout: " . ini_get('default_socket_timeout') . "s\n";
    
    echo "   📋 Extensiones requeridas:\n";
    echo "      - OpenSSL: " . (extension_loaded('openssl') ? 'Cargado ✅' : 'No cargado ❌') . "\n";
    echo "      - SOAP: " . (extension_loaded('soap') ? 'Cargado ✅' : 'No cargado ❌') . "\n";
    echo "      - cURL: " . (extension_loaded('curl') ? 'Cargado ✅' : 'No cargado ❌') . "\n";
    
    echo "\n📍 4. Posibles soluciones:\n";
    echo "   💡 Si ves 'Could not connect to host':\n";
    echo "      1. Verificar firewall/proxy corporativo\n";
    echo "      2. Comprobar que el puerto 8118 esté abierto\n";
    echo "      3. Verificar conectividad a ventanillaunica.gob.mx\n";
    echo "      4. Aumentar timeout de conexión\n";
    echo "      5. Configurar proxy si es necesario\n\n";
    
    echo "   💡 Configuración recomendada para SoapClient:\n";
    echo "      - connection_timeout: 60 (en lugar de 30)\n";
    echo "      - stream_context con timeout extendido\n";
    echo "      - Verificar configuración de proxy si aplica\n\n";

} catch (Exception $e) {
    echo "❌ Error durante diagnóstico: " . $e->getMessage() . "\n";
}

echo "=== FIN DIAGNÓSTICO ===\n";