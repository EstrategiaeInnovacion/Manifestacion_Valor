<?php
/**
 * Diagnóstico de Conectividad con Endpoint VUCEM
 * 
 * Este script verifica la conectividad y disponibilidad del servicio
 * ConsultarEdocument de VUCEM
 */

echo "=== DIAGNÓSTICO DE ENDPOINT VUCEM ===\n\n";

// 1. Verificar extensión SOAP
echo "1. Verificando extensión SOAP de PHP...\n";
if (!extension_loaded('soap')) {
    echo "   ❌ Extensión SOAP no está habilitada\n";
    exit(1);
}
echo "   ✅ Extensión SOAP habilitada\n\n";

// 2. Verificar extensión OpenSSL
echo "2. Verificando extensión OpenSSL de PHP...\n";
if (!extension_loaded('openssl')) {
    echo "   ❌ Extensión OpenSSL no está habilitada\n";
    exit(1);
}
echo "   ✅ Extensión OpenSSL habilitada\n\n";

// 3. Probar acceso al WSDL
$wsdlPath = __DIR__ . '/mve_vucem/ConsultarEdocument.wsdl';
echo "3. Verificando archivo WSDL local...\n";
echo "   Ruta: {$wsdlPath}\n";
if (!file_exists($wsdlPath)) {
    echo "   ❌ Archivo WSDL no encontrado\n";
    exit(1);
}
echo "   ✅ Archivo WSDL encontrado\n\n";

// 4. Cargar WSDL y obtener información
echo "4. Cargando WSDL y obteniendo información...\n";
try {
    $wsdl = new DOMDocument();
    $wsdl->load($wsdlPath);
    
    // Extraer el endpoint
    $xpath = new DOMXPath($wsdl);
    $xpath->registerNamespace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
    $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/wsdl/soap/');
    
    $addresses = $xpath->query('//soap:address/@location');
    if ($addresses->length > 0) {
        $endpoint = $addresses->item(0)->nodeValue;
        echo "   ✅ Endpoint encontrado: {$endpoint}\n";
        
        // Analizar el endpoint
        $parsedUrl = parse_url($endpoint);
        echo "   - Protocolo: " . ($parsedUrl['scheme'] ?? 'N/A') . "\n";
        echo "   - Host: " . ($parsedUrl['host'] ?? 'N/A') . "\n";
        echo "   - Puerto: " . ($parsedUrl['port'] ?? 'default') . "\n";
        echo "   - Path: " . ($parsedUrl['path'] ?? '/') . "\n\n";
        
        // 5. Verificar resolución DNS
        echo "5. Verificando resolución DNS del host...\n";
        $host = $parsedUrl['host'] ?? null;
        if ($host) {
            $ip = gethostbyname($host);
            if ($ip === $host) {
                echo "   ⚠️  No se pudo resolver el host DNS\n";
            } else {
                echo "   ✅ Host resuelto a IP: {$ip}\n";
            }
        }
        echo "\n";
        
        // 6. Probar conectividad HTTP básica
        echo "6. Probando conectividad HTTP/HTTPS básica...\n";
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'User-Agent: PHP SOAP Client'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $result = @file_get_contents($endpoint, false, $context);
        if ($result === false) {
            $error = error_get_last();
            echo "   ❌ No se pudo conectar al endpoint\n";
            echo "   Error: " . ($error['message'] ?? 'Desconocido') . "\n";
            
            // Intentar con curl si está disponible
            if (function_exists('curl_init')) {
                echo "\n   Intentando con cURL...\n";
                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                
                $curlResult = curl_exec($ch);
                $curlError = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                echo "   - HTTP Code: {$httpCode}\n";
                if ($curlError) {
                    echo "   - cURL Error: {$curlError}\n";
                } else {
                    echo "   ✅ Conexión cURL exitosa\n";
                }
            }
        } else {
            echo "   ✅ Endpoint accesible\n";
            echo "   - Longitud respuesta: " . strlen($result) . " bytes\n";
        }
        echo "\n";
        
        // 7. Intentar crear cliente SOAP
        echo "7. Intentando crear cliente SOAP...\n";
        try {
            $soapOptions = [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 30,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'soap_version' => SOAP_1_1,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            ];
            
            $client = new SoapClient($wsdlPath, $soapOptions);
            echo "   ✅ Cliente SOAP creado exitosamente\n";
            
            // Obtener funciones disponibles
            echo "\n   Funciones disponibles:\n";
            $functions = $client->__getFunctions();
            foreach ($functions as $function) {
                echo "   - {$function}\n";
            }
            
            echo "\n   Tipos disponibles:\n";
            $types = $client->__getTypes();
            foreach (array_slice($types, 0, 5) as $type) {
                echo "   - " . substr($type, 0, 100) . "...\n";
            }
            
            echo "\n   ✅ Cliente SOAP configurado correctamente\n";
            
        } catch (SoapFault $e) {
            echo "   ❌ Error creando cliente SOAP\n";
            echo "   Faultcode: {$e->faultcode}\n";
            echo "   Faultstring: {$e->faultstring}\n";
            echo "   Message: {$e->getMessage()}\n";
        }
        
    } else {
        echo "   ❌ No se encontró el endpoint en el WSDL\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error cargando WSDL: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNÓSTICO COMPLETADO ===\n";
echo "\nNOTA: Si el endpoint no está accesible, puede ser debido a:\n";
echo "- El servicio de VUCEM no está disponible públicamente\n";
echo "- Se requiere VPN o acceso especial a la red\n";
echo "- El endpoint está en una intranet/red privada\n";
echo "- Problemas de firewall o proxy\n";
echo "- El servicio está temporalmente fuera de línea\n";
