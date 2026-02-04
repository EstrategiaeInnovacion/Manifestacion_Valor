<?php
/**
 * Conversor de Llave Privada DER a PEM
 * Convierte llaves privadas binarias (DER) del SAT a formato PEM compatible
 */

echo "=== CONVERSOR LLAVE PRIVADA DER A PEM ===\n\n";

// CONFIGURACIÓN - Cambia estas rutas por tus archivos reales
$llavePrivadaPath = __DIR__ . '/llave_privada_binaria.key';  // Tu archivo .key del SAT
$outputPath = __DIR__ . '/llave_privada_convertida.pem';     // Archivo de salida
$password = 'NetxicoEM9';  // Tu contraseña de eFirma

echo "Archivo de entrada: {$llavePrivadaPath}\n";
echo "Archivo de salida: {$outputPath}\n";
echo "Contraseña configurada: " . str_repeat('*', strlen($password)) . "\n\n";

if (!file_exists($llavePrivadaPath)) {
    echo "❌ ERROR: El archivo {$llavePrivadaPath} no existe.\n";
    echo "Coloca tu archivo .key del SAT en esa ruta o cambia la variable \$llavePrivadaPath\n";
    exit(1);
}

$content = file_get_contents($llavePrivadaPath);
if ($content === false) {
    echo "❌ ERROR: No se pudo leer el archivo\n";
    exit(1);
}

echo "1. Analizando archivo de llave privada...\n";
$fileSize = strlen($content);
echo "   - Tamaño: " . number_format($fileSize) . " bytes\n";

// Detectar formato
$isPEM = strpos($content, '-----BEGIN') !== false;
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
    
    // Verificar si ya funciona
    $testKey = @openssl_pkey_get_private($content, $password);
    if ($testKey !== false) {
        openssl_free_key($testKey);
        echo "   ✅ La llave PEM ya es válida y funciona con la contraseña\n";
        echo "   No necesita conversión, puede usar el archivo tal como está.\n";
        exit(0);
    } else {
        echo "   ❌ La llave PEM no funciona con la contraseña proporcionada\n";
    }
}

if (!$isDER) {
    echo "\n❌ ERROR: El archivo no parece ser una llave privada en formato DER válido\n";
    exit(1);
}

echo "\n2. Intentando conversión usando diferentes métodos...\n";

// Método 1: Intentar conversión manual básica para RSA
echo "   Método 1: Conversión básica DER a PEM...\n";
$pemContent = "-----BEGIN PRIVATE KEY-----\n";
$pemContent .= chunk_split(base64_encode($content), 64, "\n");
$pemContent .= "-----END PRIVATE KEY-----\n";

$testKey = @openssl_pkey_get_private($pemContent, $password);
if ($testKey !== false) {
    openssl_free_key($testKey);
    echo "   ✅ Conversión básica exitosa\n";
} else {
    echo "   ❌ Conversión básica falló\n";
    
    // Método 2: Intentar como RSA privada
    echo "   Método 2: Intentando como RSA PRIVATE KEY...\n";
    $pemContent = "-----BEGIN RSA PRIVATE KEY-----\n";
    $pemContent .= chunk_split(base64_encode($content), 64, "\n");
    $pemContent .= "-----END RSA PRIVATE KEY-----\n";
    
    $testKey = @openssl_pkey_get_private($pemContent, $password);
    if ($testKey !== false) {
        openssl_free_key($testKey);
        echo "   ✅ Conversión como RSA exitosa\n";
    } else {
        echo "   ❌ Conversión como RSA falló\n";
        
        echo "\n❌ ERROR: No se pudo convertir la llave privada\n";
        echo "Posibles causas:\n";
        echo "1. El archivo no es una llave privada DER válida\n";
        echo "2. La contraseña '{$password}' es incorrecta\n";
        echo "3. El archivo podría estar en formato PKCS#12 (.p12/.pfx)\n";
        echo "4. El formato DER no es compatible con esta conversión\n";
        
        // Intentar sin contraseña
        echo "\n   Probando sin contraseña...\n";
        $testKey = @openssl_pkey_get_private($pemContent);
        if ($testKey !== false) {
            openssl_free_key($testKey);
            echo "   ⚠️  La llave funciona SIN contraseña\n";
            echo "   Tu llave privada no está protegida por contraseña\n";
            $password = ''; // Sin contraseña para el archivo final
        } else {
            echo "   ❌ Tampoco funciona sin contraseña\n";
            exit(1);
        }
    }
}

echo "\n3. Validando llave convertida...\n";

// Verificar detalles de la llave
$keyResource = openssl_pkey_get_private($pemContent, $password);
$keyDetails = openssl_pkey_get_details($keyResource);
openssl_free_key($keyResource);

echo "   ✅ Llave convertida es válida\n";
echo "   - Tipo: " . $keyDetails['type'] . " (RSA=" . OPENSSL_KEYTYPE_RSA . ")\n";
echo "   - Bits: " . $keyDetails['bits'] . "\n";

if ($keyDetails['type'] !== OPENSSL_KEYTYPE_RSA) {
    echo "   ⚠️  ADVERTENCIA: La llave no es RSA, podría no ser compatible con VUCEM\n";
}

echo "\n4. Guardando archivo convertido...\n";
$saved = file_put_contents($outputPath, $pemContent);

if ($saved === false) {
    echo "   ❌ ERROR: No se pudo guardar el archivo convertido\n";
    exit(1);
}

echo "   ✅ Archivo guardado exitosamente: {$outputPath}\n";
echo "   - Bytes escritos: " . number_format($saved) . "\n";

echo "\n5. Test final de compatibilidad...\n";

// Simular el test de la aplicación
$finalTest = @openssl_pkey_get_private($pemContent, $password);
if ($finalTest !== false) {
    openssl_free_key($finalTest);
    echo "   ✅ El archivo convertido funcionará en la aplicación\n";
} else {
    echo "   ❌ El archivo convertido podría tener problemas\n";
}

echo "\n=== CONVERSIÓN EXITOSA ===\n";
echo "Tu llave privada ha sido convertida de formato binario (DER) a formato texto (PEM).\n";
echo "\nInstrucciones para usar en la aplicación:\n";
echo "1. Usa el archivo convertido: {$outputPath}\n";
echo "2. Contraseña: " . ($password ? $password : "[SIN CONTRASEÑA]") . "\n";
echo "3. Sube este archivo .pem en lugar del .key original\n";
echo "4. La aplicación debería aceptar la llave correctamente\n";

echo "\nArchivos:\n";
echo "- Original (DER): {$llavePrivadaPath} (" . number_format(filesize($llavePrivadaPath)) . " bytes)\n";
echo "- Convertido (PEM): {$outputPath} (" . number_format(filesize($outputPath)) . " bytes)\n";

echo "\n¡Listo para usar en la aplicación!\n";