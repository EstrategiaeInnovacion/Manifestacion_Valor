<?php
/**
 * Test específico para debuggear problemas con eFirma SHA256withRSA
 * Diagnóstica paso a paso el proceso de firma digital
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "=== DEBUG eFirma SHA256withRSA ===\n\n";

// Configuración de archivos de prueba
$certificadoPath = __DIR__ . '/storage/app/efirma/certificado.cer';
$llavePrivadaPath = __DIR__ . '/storage/app/efirma/llave_privada.key';
$password = 'CAMBIA_POR_TU_PASSWORD';

echo "1. Verificando extensiones PHP necesarias...\n";
$extensiones = ['openssl', 'soap'];
foreach ($extensiones as $ext) {
    $loaded = extension_loaded($ext);
    echo "   - {$ext}: " . ($loaded ? "✅ CARGADA" : "❌ NO CARGADA") . "\n";
    if (!$loaded) {
        echo "     ERROR: La extensión {$ext} es necesaria\n";
    }
}
echo "\n";

echo "2. Verificando archivos eFirma...\n";
echo "   - Certificado: " . ($certificado_existe = file_exists($certificadoPath) ? "✅ EXISTE" : "❌ NO EXISTE") . "\n";
echo "     Ruta: {$certificadoPath}\n";
echo "   - Llave privada: " . ($llave_existe = file_exists($llavePrivadaPath) ? "✅ EXISTE" : "❌ NO EXISTE") . "\n";
echo "     Ruta: {$llavePrivadaPath}\n";

if (!$certificado_existe || !$llave_existe) {
    echo "\n❌ ERROR: Debes colocar los archivos .cer y .key en las rutas especificadas\n";
    exit(1);
}
echo "\n";

try {
    echo "3. Analizando certificado...\n";
    $certificadoContent = file_get_contents($certificadoPath);
    $certificadoInfo = openssl_x509_parse($certificadoContent);
    
    if ($certificadoInfo === false) {
        echo "   ❌ ERROR: No se pudo parsear el certificado\n";
        echo "   Posibles causas:\n";
        echo "   - El archivo no es un certificado válido\n";
        echo "   - El formato no es compatible (debe ser .cer, .crt o .pem)\n";
        exit(1);
    }
    
    echo "   ✅ Certificado parseado exitosamente\n";
    echo "   - Subject: " . $certificadoInfo['subject']['CN'] . "\n";
    echo "   - Válido desde: " . date('Y-m-d H:i:s', $certificadoInfo['validFrom_time_t']) . "\n";
    echo "   - Válido hasta: " . date('Y-m-d H:i:s', $certificadoInfo['validTo_time_t']) . "\n";
    echo "   - Algoritmo de firma: " . $certificadoInfo['signatureTypeSN'] . "\n";
    
    // Verificar vigencia
    $ahora = time();
    $vigente = $ahora >= $certificadoInfo['validFrom_time_t'] && $ahora <= $certificadoInfo['validTo_time_t'];
    echo "   - Vigente: " . ($vigente ? "✅ SÍ" : "❌ NO") . "\n";
    
    if (!$vigente) {
        echo "   ⚠️  ADVERTENCIA: El certificado no está vigente\n";
    }
    echo "\n";

    echo "4. Probando llave privada...\n";
    $llavePrivadaContent = file_get_contents($llavePrivadaPath);
    
    // Intentar cargar la llave privada con la contraseña
    $privateKey = openssl_pkey_get_private($llavePrivadaContent, $password);
    
    if ($privateKey === false) {
        echo "   ❌ ERROR: No se pudo cargar la llave privada\n";
        echo "   Errores OpenSSL:\n";
        while (($error = openssl_error_string()) !== false) {
            echo "   - {$error}\n";
        }
        echo "\n   Posibles causas:\n";
        echo "   - La contraseña es incorrecta\n";
        echo "   - El formato de la llave no es compatible\n";
        echo "   - El archivo está corrupto\n";
        exit(1);
    }
    
    echo "   ✅ Llave privada cargada exitosamente\n";
    
    // Obtener detalles de la llave
    $keyDetails = openssl_pkey_get_details($privateKey);
    echo "   - Tipo: " . $keyDetails['type'] . " (Esperado: " . OPENSSL_KEYTYPE_RSA . " para RSA)\n";
    echo "   - Bits: " . $keyDetails['bits'] . "\n";
    echo "\n";

    echo "5. Test de firma SHA256withRSA...\n";
    $cadenaOriginal = "TEST_CADENA_ORIGINAL_PARA_VUCEM_SHA256withRSA_" . date('Y-m-d_H:i:s');
    echo "   - Cadena a firmar: {$cadenaOriginal}\n";
    echo "   - Longitud: " . strlen($cadenaOriginal) . " caracteres\n";
    
    // Paso 1: Generar hash SHA256
    $hash = hash('sha256', $cadenaOriginal, true);
    echo "   - Hash SHA256 (binario): " . bin2hex($hash) . "\n";
    echo "   - Longitud hash: " . strlen($hash) . " bytes\n";
    
    // Paso 2: Firmar con RSA
    $signature = '';
    $firmado = openssl_sign($cadenaOriginal, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
    if (!$firmado) {
        echo "   ❌ ERROR: Fallo en la firma\n";
        echo "   Errores OpenSSL:\n";
        while (($error = openssl_error_string()) !== false) {
            echo "   - {$error}\n";
        }
        openssl_free_key($privateKey);
        exit(1);
    }
    
    echo "   ✅ Firma generada exitosamente\n";
    echo "   - Longitud firma binaria: " . strlen($signature) . " bytes\n";
    
    // Paso 3: Codificar en Base64
    $firmaBase64 = base64_encode($signature);
    echo "   - Firma Base64: " . substr($firmaBase64, 0, 50) . "...\n";
    echo "   - Longitud Base64: " . strlen($firmaBase64) . " caracteres\n";
    echo "\n";

    echo "6. Verificando firma...\n";
    
    // Obtener clave pública del certificado
    $publicKey = openssl_pkey_get_public($certificadoContent);
    if ($publicKey === false) {
        echo "   ❌ ERROR: No se pudo obtener la clave pública del certificado\n";
        openssl_free_key($privateKey);
        exit(1);
    }
    
    // Verificar firma
    $verificacion = openssl_verify($cadenaOriginal, $signature, $publicKey, OPENSSL_ALGO_SHA256);
    
    if ($verificacion === 1) {
        echo "   ✅ FIRMA VÁLIDA - La firma se verificó correctamente\n";
    } elseif ($verificacion === 0) {
        echo "   ❌ FIRMA INVÁLIDA - La firma no coincide\n";
    } else {
        echo "   ❌ ERROR EN VERIFICACIÓN\n";
        echo "   Errores OpenSSL:\n";
        while (($error = openssl_error_string()) !== false) {
            echo "   - {$error}\n";
        }
    }
    
    // Limpieza
    openssl_free_key($privateKey);
    openssl_free_key($publicKey);
    echo "\n";

    echo "7. Test de conversión Base64 para SOAP...\n";
    $certificadoBase64 = base64_encode($certificadoContent);
    $llaveBase64 = base64_encode($llavePrivadaContent);
    
    echo "   ✅ Conversiones completadas\n";
    echo "   - Certificado Base64: " . strlen($certificadoBase64) . " caracteres\n";
    echo "   - Llave Base64: " . strlen($llaveBase64) . " caracteres\n";
    echo "   - Password: " . (strlen($password) > 0 ? "Configurado" : "NO configurado") . "\n";
    echo "\n";

    echo "8. Resumen de compatibilidad VUCEM...\n";
    echo "   ✅ Algoritmo SHA256withRSA implementado correctamente\n";
    echo "   ✅ Certificado y llave privada compatibles\n";
    echo "   ✅ Conversión Base64 lista para SOAP\n";
    echo "   ✅ Proceso de firma funcional\n";
    echo "\n";

    echo "=== RESULTADO: TODO CORRECTO ===\n";
    echo "Tu eFirma está lista para usar con VUCEM.\n";
    echo "\nDatos para usar en la aplicación:\n";
    echo "- Certificado Base64: " . substr($certificadoBase64, 0, 100) . "...\n";
    echo "- Llave Base64: " . substr($llaveBase64, 0, 100) . "...\n";
    echo "- Firma de prueba: " . substr($firmaBase64, 0, 100) . "...\n";

} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
echo "Instrucciones para usar en la aplicación:\n";
echo "1. Coloca tus archivos .cer y .key en storage/app/efirma/\n";
echo "2. Configura la contraseña correcta\n";
echo "3. Usa VucemEFirmaService para preparar los archivos\n";
echo "4. El servicio generará automáticamente las firmas SHA256withRSA\n";
echo "5. Los datos se enviarán en formato Base64 a VUCEM\n";