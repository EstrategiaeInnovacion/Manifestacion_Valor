<?php
/**
 * Script para diagnosticar problemas con archivos eFirma reales
 * Ayuda a identificar qué está causando el rechazo de archivos válidos
 */

echo "=== DIAGNÓSTICO ARCHIVOS eFirma REALES ===\n\n";

// Cambiar estas rutas por las de tus archivos reales
$certificadoPath = __DIR__ . '/certificado_real.cer';  // Cambia por tu archivo
$llavePrivadaPath = __DIR__ . '/llave_privada_real.key';  // Cambia por tu archivo
$password = 'tu_password_real_aqui';  // Cambia por tu contraseña real

echo "IMPORTANTE: Cambia las rutas y contraseña en este archivo antes de ejecutar\n";
echo "Archivos a verificar:\n";
echo "- Certificado: {$certificadoPath}\n";
echo "- Llave: {$llavePrivadaPath}\n";
echo "- Password: " . (strlen($password) > 3 ? str_repeat('*', strlen($password)) : 'NO_CONFIGURADO') . "\n\n";

// 1. Verificar que los archivos existan
echo "1. Verificando existencia de archivos...\n";
$certExists = file_exists($certificadoPath);
$keyExists = file_exists($llavePrivadaPath);

echo "   - Certificado: " . ($certExists ? "✅ EXISTE" : "❌ NO EXISTE") . "\n";
echo "   - Llave privada: " . ($keyExists ? "✅ EXISTE" : "❌ NO EXISTE") . "\n";

if (!$certExists || !$keyExists) {
    echo "\n❌ ERROR: Coloca los archivos en las rutas correctas\n";
    exit(1);
}

// 2. Información básica de archivos
echo "\n2. Información de archivos...\n";
$certSize = filesize($certificadoPath);
$keySize = filesize($llavePrivadaPath);

echo "   - Tamaño certificado: " . number_format($certSize) . " bytes\n";
echo "   - Tamaño llave: " . number_format($keySize) . " bytes\n";

// 3. Análisis de extensiones
echo "\n3. Análisis de extensiones...\n";
$certExt = strtolower(pathinfo($certificadoPath, PATHINFO_EXTENSION));
$keyExt = strtolower(pathinfo($llavePrivadaPath, PATHINFO_EXTENSION));

$validCertExts = ['cer', 'crt', 'pem', 'der', 'p7b', 'p7c'];
$validKeyExts = ['key', 'pem', 'p8', 'der'];

echo "   - Extensión certificado: .{$certExt} " . (in_array($certExt, $validCertExts) ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";
echo "   - Extensión llave: .{$keyExt} " . (in_array($keyExt, $validKeyExts) ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";

// 4. Leer contenido de archivos
echo "\n4. Leyendo contenido de archivos...\n";
$certContent = file_get_contents($certificadoPath);
$keyContent = file_get_contents($llavePrivadaPath);

if ($certContent === false) {
    echo "   ❌ ERROR: No se pudo leer el certificado\n";
    exit(1);
}

if ($keyContent === false) {
    echo "   ❌ ERROR: No se pudo leer la llave privada\n";
    exit(1);
}

echo "   ✅ Archivos leídos correctamente\n";

// 5. Análisis del certificado
echo "\n5. Analizando certificado...\n";

// Detectar formato del certificado
if (strpos($certContent, '-----BEGIN CERTIFICATE-----') !== false) {
    echo "   - Formato: PEM (Base64 con headers)\n";
} elseif (strpos($certContent, '-----BEGIN') !== false) {
    echo "   - Formato: PEM (otro tipo)\n";
} else {
    echo "   - Formato: Binario (DER probablemente)\n";
}

// Intentar parsear
$cert = @openssl_x509_parse($certContent);
if ($cert === false) {
    echo "   ❌ ERROR: No se pudo parsear el certificado\n";
    echo "   Errores OpenSSL:\n";
    while (($error = openssl_error_string()) !== false) {
        echo "   - {$error}\n";
    }
    
    // Intentar otros métodos
    echo "\n   Intentando método alternativo...\n";
    $tempFile = tempnam(sys_get_temp_dir(), 'cert');
    file_put_contents($tempFile, $certContent);
    $cert = @openssl_x509_parse(file_get_contents($tempFile));
    unlink($tempFile);
    
    if ($cert === false) {
        echo "   ❌ ERROR: Certificado no válido con ningún método\n";
        echo "\n   Posibles causas:\n";
        echo "   - El archivo no es un certificado X.509\n";
        echo "   - El certificado está dañado\n";
        echo "   - El formato no es compatible con OpenSSL\n";
    } else {
        echo "   ✅ Certificado válido con método alternativo\n";
    }
} else {
    echo "   ✅ Certificado parseado exitosamente\n";
    echo "   - Emisor: " . $cert['issuer']['CN'] . "\n";
    echo "   - Sujeto: " . $cert['subject']['CN'] . "\n";
    echo "   - Válido desde: " . date('Y-m-d', $cert['validFrom_time_t']) . "\n";
    echo "   - Válido hasta: " . date('Y-m-d', $cert['validTo_time_t']) . "\n";
    
    $now = time();
    $vigente = $now >= $cert['validFrom_time_t'] && $now <= $cert['validTo_time_t'];
    echo "   - Vigente: " . ($vigente ? "✅ SÍ" : "❌ NO") . "\n";
}

// 6. Análisis de la llave privada
echo "\n6. Analizando llave privada...\n";

// Detectar formato de la llave
if (strpos($keyContent, '-----BEGIN PRIVATE KEY-----') !== false) {
    echo "   - Formato: PEM (PKCS#8)\n";
} elseif (strpos($keyContent, '-----BEGIN RSA PRIVATE KEY-----') !== false) {
    echo "   - Formato: PEM (PKCS#1 RSA)\n";
} elseif (strpos($keyContent, '-----BEGIN ENCRYPTED PRIVATE KEY-----') !== false) {
    echo "   - Formato: PEM (PKCS#8 Encriptado)\n";
} elseif (strpos($keyContent, '-----BEGIN') !== false) {
    echo "   - Formato: PEM (otro tipo)\n";
} else {
    echo "   - Formato: Binario (DER/PKCS#12 probablemente)\n";
}

// Intentar cargar la llave
$privateKey = @openssl_pkey_get_private($keyContent, $password);
if ($privateKey === false) {
    echo "   ❌ ERROR: No se pudo cargar la llave privada\n";
    echo "   Errores OpenSSL:\n";
    while (($error = openssl_error_string()) !== false) {
        echo "   - {$error}\n";
    }
    
    echo "\n   Posibles causas:\n";
    echo "   - La contraseña es incorrecta\n";
    echo "   - El formato de la llave no es compatible\n";
    echo "   - La llave está dañada o corrupta\n";
    echo "   - La llave no coincide con el certificado\n";
    
    // Intentar sin contraseña
    echo "\n   Intentando cargar sin contraseña...\n";
    $privateKey = @openssl_pkey_get_private($keyContent);
    if ($privateKey !== false) {
        echo "   ⚠️  La llave se cargó SIN contraseña (no está protegida)\n";
        openssl_free_key($privateKey);
    } else {
        echo "   ❌ No se pudo cargar ni con ni sin contraseña\n";
    }
    
} else {
    echo "   ✅ Llave privada cargada exitosamente\n";
    
    $keyDetails = openssl_pkey_get_details($privateKey);
    echo "   - Tipo: " . $keyDetails['type'] . " (RSA=" . OPENSSL_KEYTYPE_RSA . ")\n";
    echo "   - Bits: " . $keyDetails['bits'] . "\n";
    
    openssl_free_key($privateKey);
}

// 7. Test de compatibilidad Laravel
echo "\n7. Test de compatibilidad con Laravel...\n";

// Simular validación de Laravel
$fakeFile = new class($certificadoPath) {
    private $path;
    public function __construct($path) { $this->path = $path; }
    public function getClientOriginalExtension() { return pathinfo($this->path, PATHINFO_EXTENSION); }
    public function getPathname() { return $this->path; }
    public function get() { return file_get_contents($this->path); }
};

$certFile = new $fakeFile($certificadoPath);
$keyFile = new $fakeFile($llavePrivadaPath);

// Validación de extensiones
$certExtValid = in_array(strtolower($certFile->getClientOriginalExtension()), $validCertExts);
$keyExtValid = in_array(strtolower($keyFile->getClientOriginalExtension()), $validKeyExts);

echo "   - Validación extensión certificado: " . ($certExtValid ? "✅ PASARÍA" : "❌ FALLARÍA") . "\n";
echo "   - Validación extensión llave: " . ($keyExtValid ? "✅ PASARÍA" : "❌ FALLARÍA") . "\n";

// Validación OpenSSL
$certOpenSSLValid = @openssl_x509_parse($certFile->get()) !== false;
$keyOpenSSLValid = @openssl_pkey_get_private($keyFile->get(), $password) !== false;

echo "   - Validación OpenSSL certificado: " . ($certOpenSSLValid ? "✅ PASARÍA" : "❌ FALLARÍA") . "\n";
echo "   - Validación OpenSSL llave: " . ($keyOpenSSLValid ? "✅ PASARÍA" : "❌ FALLARÍA") . "\n";

// 8. Resumen final
echo "\n8. RESUMEN FINAL\n";
echo "================\n";

if ($certExtValid && $keyExtValid && $certOpenSSLValid && $keyOpenSSLValid) {
    echo "✅ TODOS LOS TESTS PASARON\n";
    echo "   Tus archivos deberían funcionar correctamente en la aplicación.\n";
    
    // Mostrar datos Base64 para debug
    echo "\n   Datos Base64 (para debug):\n";
    echo "   - Certificado: " . substr(base64_encode($certContent), 0, 50) . "...\n";
    echo "   - Llave: " . substr(base64_encode($keyContent), 0, 50) . "...\n";
    
} else {
    echo "❌ HAY PROBLEMAS CON LOS ARCHIVOS\n";
    echo "\nProblemas identificados:\n";
    
    if (!$certExtValid) echo "   - Extensión del certificado no válida\n";
    if (!$keyExtValid) echo "   - Extensión de la llave no válida\n";
    if (!$certOpenSSLValid) echo "   - Certificado no válido para OpenSSL\n";
    if (!$keyOpenSSLValid) echo "   - Llave privada no válida o contraseña incorrecta\n";
    
    echo "\nSoluciones sugeridas:\n";
    echo "   1. Verifica que los archivos sean de tu eFirma oficial\n";
    echo "   2. Confirma que la contraseña sea correcta\n";
    echo "   3. Intenta convertir a formato PEM si están en DER\n";
    echo "   4. Contacta al SAT si los archivos fueron generados recientemente\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";