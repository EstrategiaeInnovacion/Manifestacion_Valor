<?php
require_once __DIR__ . '/vendor/autoload.php';

// Script específico para llaves encriptadas del SAT en formato PKCS#8
function procesarLlaveSAT($llavePrivadaPath, $password) {
    echo "=== PROCESAMIENTO ESPECIALIZADO PARA LLAVES SAT ===\n\n";
    
    if (!file_exists($llavePrivadaPath)) {
        echo "❌ Archivo no encontrado: {$llavePrivadaPath}\n";
        return false;
    }
    
    $keyContent = file_get_contents($llavePrivadaPath);
    echo "📏 Tamaño del archivo: " . strlen($keyContent) . " bytes\n";
    echo "🔍 Primeros bytes: " . bin2hex(substr($keyContent, 0, 20)) . "\n";
    
    // Las llaves del SAT son PKCS#8 encriptadas en formato DER
    // Intentar usar openssl_pkcs12_parse si no funciona el método estándar
    
    echo "\n🧪 MÉTODOS DE PROCESAMIENTO:\n";
    
    // Método 1: Usar comando OpenSSL externo para convertir
    echo "1️⃣  Conversión usando OpenSSL externo:\n";
    $tempDerFile = tempnam(sys_get_temp_dir(), 'key_der_');
    $tempPemFile = tempnam(sys_get_temp_dir(), 'key_pem_');
    
    file_put_contents($tempDerFile, $keyContent);
    
    // Comando para convertir PKCS#8 DER a PEM
    $cmd = "openssl pkcs8 -inform DER -outform PEM -in \"{$tempDerFile}\" -out \"{$tempPemFile}\" -passin pass:\"{$password}\"";
    
    exec($cmd . " 2>&1", $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($tempPemFile)) {
        $pemContent = file_get_contents($tempPemFile);
        echo "   ✅ Conversión exitosa con OpenSSL externo\n";
        echo "   📄 Contenido PEM generado (" . strlen($pemContent) . " bytes)\n";
        
        // Probar cargar la llave PEM
        $pkey = openssl_pkey_get_private($pemContent);
        if ($pkey) {
            echo "   🔑 Llave cargada exitosamente\n";
            
            // Probar firma
            $testString = "Cadena de prueba para firma";
            $signature = '';
            $success = openssl_sign($testString, $signature, $pkey, OPENSSL_ALGO_SHA256);
            
            if ($success) {
                echo "   ✅ FIRMA EXITOSA\n";
                echo "   📝 Firma (primeros 50 chars): " . substr(base64_encode($signature), 0, 50) . "...\n";
                
                // Mostrar el PEM convertido para usar en el código
                echo "\n📋 CONTENIDO PEM PARA USAR:\n";
                echo "---INICIO---\n";
                echo $pemContent;
                echo "---FIN---\n";
            }
            
            openssl_free_key($pkey);
        } else {
            echo "   ❌ Error al cargar llave PEM: " . openssl_error_string() . "\n";
        }
        
    } else {
        echo "   ❌ Error en conversión OpenSSL: " . implode("\n   ", $output) . "\n";
    }
    
    // Limpiar archivos temporales
    @unlink($tempDerFile);
    @unlink($tempPemFile);
    
    // Método 2: Usar openssl_pkey_get_private con diferentes parámetros
    echo "\n2️⃣  Carga directa con parámetros específicos:\n";
    
    // Intentar con configuración específica para PKCS#8
    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    
    $pkey = @openssl_pkey_get_private($keyContent, $password);
    if ($pkey) {
        echo "   ✅ Cargada directamente\n";
        openssl_free_key($pkey);
    } else {
        echo "   ❌ Error: " . openssl_error_string() . "\n";
    }
    
    echo "\n=== FIN DEL PROCESAMIENTO ===\n";
}

// Verificar argumentos
if ($argc < 3) {
    echo "Uso: php debug_sat_keys.php <llave.key> <contraseña>\n";
    exit(1);
}

$llavePrivadaPath = $argv[1];
$password = $argv[2];

procesarLlaveSAT($llavePrivadaPath, $password);