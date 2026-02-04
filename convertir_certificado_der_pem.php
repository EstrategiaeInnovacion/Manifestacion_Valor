<?php
/**
 * Herramienta para convertir certificados DER (binarios) a PEM
 * Especialmente útil para certificados del SAT que vienen en formato .cer binario
 */

echo "=== CONVERSOR CERTIFICADO DER A PEM ===\n\n";

// Configurar la ruta de tu certificado
$certificadoPath = __DIR__ . '/certificado_a_convertir.cer';  // CAMBIA ESTA RUTA
$outputPath = __DIR__ . '/certificado_convertido.pem';

echo "Archivo a convertir: {$certificadoPath}\n";
echo "Archivo de salida: {$outputPath}\n\n";

if (!file_exists($certificadoPath)) {
    echo "❌ ERROR: El archivo {$certificadoPath} no existe.\n";
    echo "Coloca tu certificado .cer en esa ruta o cambia la variable \$certificadoPath\n";
    exit(1);
}

$content = file_get_contents($certificadoPath);
if ($content === false) {
    echo "❌ ERROR: No se pudo leer el archivo\n";
    exit(1);
}

echo "1. Analizando formato del certificado...\n";
$fileSize = strlen($content);
echo "   - Tamaño: " . number_format($fileSize) . " bytes\n";

// Detectar formato
$isPEM = strpos($content, '-----BEGIN CERTIFICATE-----') !== false;
$isDER = false;

if (!$isPEM) {
    // Verificar si parece DER
    if ($fileSize > 0 && ord($content[0]) === 0x30) {
        $isDER = true;
        echo "   - Formato detectado: DER (binario)\n";
    } else {
        echo "   - Formato: Desconocido\n";
    }
} else {
    echo "   - Formato detectado: PEM (ya convertido)\n";
}

echo "\n2. Intentando parsear certificado original...\n";
$cert = @openssl_x509_parse($content);
if ($cert !== false) {
    echo "   ✅ El certificado ya es válido y se puede parsear\n";
    echo "   - Sujeto: " . $cert['subject']['CN'] . "\n";
    echo "   - Válido desde: " . date('Y-m-d', $cert['validFrom_time_t']) . "\n";
    echo "   - Válido hasta: " . date('Y-m-d', $cert['validTo_time_t']) . "\n";
    
    if ($isPEM) {
        echo "\n✅ El certificado ya está en formato PEM, no necesita conversión.\n";
        exit(0);
    }
}

if ($isDER || $cert === false) {
    echo "\n3. Intentando conversión DER a PEM...\n";
    
    // Convertir DER a PEM
    $base64Content = base64_encode($content);
    $pemContent = "-----BEGIN CERTIFICATE-----\n";
    $pemContent .= chunk_split($base64Content, 64, "\n");
    $pemContent .= "-----END CERTIFICATE-----\n";
    
    echo "   - Conversión completada\n";
    echo "   - Tamaño PEM: " . strlen($pemContent) . " bytes\n";
    
    // Verificar que el PEM convertido sea válido
    echo "\n4. Validando certificado convertido...\n";
    $certConverted = @openssl_x509_parse($pemContent);
    
    if ($certConverted === false) {
        echo "   ❌ ERROR: El certificado convertido no es válido\n";
        echo "   El archivo original no parece ser un certificado DER válido\n";
        exit(1);
    }
    
    echo "   ✅ Certificado convertido es válido\n";
    echo "   - Sujeto: " . $certConverted['subject']['CN'] . "\n";
    echo "   - Emisor: " . $certConverted['issuer']['CN'] . "\n";
    echo "   - Válido desde: " . date('Y-m-d', $certConverted['validFrom_time_t']) . "\n";
    echo "   - Válido hasta: " . date('Y-m-d', $certConverted['validTo_time_t']) . "\n";
    
    $now = time();
    $vigente = $now >= $certConverted['validFrom_time_t'] && $now <= $certConverted['validTo_time_t'];
    echo "   - Vigente: " . ($vigente ? "✅ SÍ" : "❌ NO - CERTIFICADO VENCIDO") . "\n";
    
    // Guardar el archivo convertido
    echo "\n5. Guardando archivo convertido...\n";
    $saved = file_put_contents($outputPath, $pemContent);
    
    if ($saved === false) {
        echo "   ❌ ERROR: No se pudo guardar el archivo convertido\n";
        exit(1);
    }
    
    echo "   ✅ Archivo guardado exitosamente: {$outputPath}\n";
    echo "   - Bytes escritos: " . number_format($saved) . "\n";
    
    echo "\n=== CONVERSIÓN EXITOSA ===\n";
    echo "Ahora puedes usar el archivo: {$outputPath}\n";
    echo "Este archivo PEM debería funcionar correctamente en la aplicación.\n";
    
} else {
    echo "\n❌ No se pudo determinar el formato del archivo\n";
    echo "El archivo no parece ser ni PEM ni DER válido\n";
    exit(1);
}

echo "\nInstrucciones para usar en la aplicación:\n";
echo "1. Sube el archivo convertido (.pem) en lugar del original (.cer)\n";
echo "2. O reemplaza tu archivo original con el convertido\n";
echo "3. La aplicación ahora debería aceptar el certificado\n";

// Test rápido con la aplicación
echo "\n6. Test de compatibilidad con la aplicación...\n";

// Simular la validación que hace la aplicación
function isBinaryFormat($content) {
    if (strpos($content, '-----BEGIN') !== false) {
        return false;
    }
    if (strlen($content) > 0 && ord($content[0]) === 0x30) {
        return true;
    }
    return false;
}

$originalIsBinary = isBinaryFormat($content);
$convertedIsBinary = isBinaryFormat($pemContent);

echo "   - Archivo original es binario: " . ($originalIsBinary ? "SÍ" : "NO") . "\n";
echo "   - Archivo convertido es binario: " . ($convertedIsBinary ? "SÍ" : "NO") . "\n";

// Test de la lógica de la aplicación
$appValidation = false;
$cert1 = @openssl_x509_parse($pemContent);
if ($cert1 !== false) {
    $appValidation = true;
} elseif (isBinaryFormat($pemContent)) {
    $pemContent2 = "-----BEGIN CERTIFICATE-----\n" . 
                  chunk_split(base64_encode($pemContent), 64, "\n") . 
                  "-----END CERTIFICATE-----\n";
    $cert2 = @openssl_x509_parse($pemContent2);
    if ($cert2 !== false) {
        $appValidation = true;
    }
}

echo "   - Pasaría validación de la app: " . ($appValidation ? "✅ SÍ" : "❌ NO") . "\n";

echo "\n¡Listo! Tu certificado ha sido convertido y debería funcionar en la aplicación.\n";