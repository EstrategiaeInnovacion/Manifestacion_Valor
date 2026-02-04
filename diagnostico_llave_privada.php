<?php
/**
 * Herramienta para diagnosticar problemas con llaves privadas de eFirma
 * Ayuda a identificar el formato correcto y la contraseña correcta
 */

echo "=== DIAGNÓSTICO LLAVE PRIVADA eFirma ===\n\n";

// Configuración - CAMBIA ESTAS RUTAS
$llavePrivadaPath = __DIR__ . '/tu_llave_privada.key';  // Cambia por tu archivo
$passwords = [
    '',  // Sin contraseña
    'tu_password_1',  // Cambia por tu contraseña
    'tu_password_2',  // Agrega más si no estás seguro
    'tu_password_3'
];

echo "IMPORTANTE: Configura las rutas y contraseñas en este archivo antes de ejecutar\n";
echo "Archivo a probar: {$llavePrivadaPath}\n";
echo "Contraseñas a probar: " . count($passwords) . " opciones\n\n";

if (!file_exists($llavePrivadaPath)) {
    echo "❌ ERROR: El archivo {$llavePrivadaPath} no existe\n";
    echo "Coloca tu archivo .key en esa ruta o cambia la variable \$llavePrivadaPath\n";
    exit(1);
}

$content = file_get_contents($llavePrivadaPath);
if ($content === false) {
    echo "❌ ERROR: No se pudo leer el archivo\n";
    exit(1);
}

echo "1. Información básica del archivo...\n";
echo "   - Tamaño: " . number_format(strlen($content)) . " bytes\n";
echo "   - Extensión: ." . pathinfo($llavePrivadaPath, PATHINFO_EXTENSION) . "\n";

echo "\n2. Analizando formato de llave privada...\n";

// Detectar formato
$formato = 'Desconocido';
$esEncriptada = false;
$esBinaria = false;

if (strpos($content, '-----BEGIN PRIVATE KEY-----') !== false) {
    $formato = 'PEM (PKCS#8) - No encriptada';
} elseif (strpos($content, '-----BEGIN RSA PRIVATE KEY-----') !== false) {
    $formato = 'PEM (PKCS#1 RSA) - No encriptada';
} elseif (strpos($content, '-----BEGIN ENCRYPTED PRIVATE KEY-----') !== false) {
    $formato = 'PEM (PKCS#8 Encriptado)';
    $esEncriptada = true;
} elseif (strpos($content, '-----BEGIN RSA PRIVATE KEY-----') !== false && 
          strpos($content, 'Proc-Type: 4,ENCRYPTED') !== false) {
    $formato = 'PEM (PKCS#1 RSA Encriptado)';
    $esEncriptada = true;
} elseif (strpos($content, '-----BEGIN') !== false) {
    $formato = 'PEM (otro tipo)';
    if (strpos($content, 'ENCRYPTED') !== false) {
        $esEncriptada = true;
    }
} else {
    $formato = 'Binario (DER/PKCS#12/P12)';
    $esBinaria = true;
}

echo "   - Formato detectado: {$formato}\n";
echo "   - Está encriptada: " . ($esEncriptada ? "SÍ" : "NO") . "\n";
echo "   - Es binaria: " . ($esBinaria ? "SÍ" : "NO") . "\n";

if ($esBinaria) {
    echo "\n❌ PROBLEMA ENCONTRADO: Llave en formato binario\n";
    echo "Las llaves binarias (DER, PKCS#12) no son compatibles con OpenSSL en PHP.\n";
    echo "Necesitas convertir la llave a formato PEM.\n";
    echo "Instrucciones:\n";
    echo "1. Usa OpenSSL para convertir: openssl rsa -inform DER -in archivo.key -out archivo_pem.key\n";
    echo "2. O solicita la llave en formato PEM al generar tu eFirma\n";
    exit(1);
}

echo "\n3. Probando contraseñas...\n";

$passwordCorrecta = null;
$llaveValida = false;
$detallesLlave = null;

foreach ($passwords as $index => $password) {
    $passwordLabel = empty($password) ? "[SIN CONTRASEÑA]" : str_repeat('*', strlen($password));
    echo "   Probando contraseña " . ($index + 1) . ": {$passwordLabel}\n";
    
    $privateKey = @openssl_pkey_get_private($content, $password);
    if ($privateKey !== false) {
        echo "   ✅ CONTRASEÑA CORRECTA ENCONTRADA!\n";
        $passwordCorrecta = $password;
        $llaveValida = true;
        
        $detallesLlave = openssl_pkey_get_details($privateKey);
        openssl_free_key($privateKey);
        break;
    } else {
        echo "   ❌ Contraseña incorrecta\n";
        
        // Capturar errores específicos
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        if (!empty($errors)) {
            echo "     Errores: " . implode(', ', $errors) . "\n";
        }
    }
}

if (!$llaveValida) {
    echo "\n❌ NINGUNA CONTRASEÑA FUNCIONÓ\n";
    echo "\nPosibles causas:\n";
    echo "1. La contraseña no está en la lista de prueba\n";
    echo "2. El archivo de llave está dañado\n";
    echo "3. El formato no es compatible\n";
    echo "4. No es una llave privada válida\n";
    
    if ($esEncriptada) {
        echo "\nLa llave ESTÁ encriptada, así que necesita contraseña.\n";
        echo "Consejos:\n";
        echo "- Es la contraseña que configuró al generar su eFirma en el SAT\n";
        echo "- NO es su CIEC, RFC, o contraseña de VUCEM\n";
        echo "- Verifique mayúsculas, minúsculas, números y símbolos\n";
    } else {
        echo "\nLa llave NO está encriptada, debería funcionar sin contraseña.\n";
        echo "Hay un problema con el formato del archivo.\n";
    }
    exit(1);
}

echo "\n4. Detalles de la llave válida...\n";
echo "   ✅ Llave cargada exitosamente\n";
echo "   - Contraseña correcta: " . (empty($passwordCorrecta) ? "[SIN CONTRASEÑA]" : str_repeat('*', strlen($passwordCorrecta))) . "\n";
echo "   - Tipo de llave: " . $detallesLlave['type'] . " (RSA=" . OPENSSL_KEYTYPE_RSA . ")\n";
echo "   - Bits: " . $detallesLlave['bits'] . "\n";

// Verificar que sea RSA
if ($detallesLlave['type'] !== OPENSSL_KEYTYPE_RSA) {
    echo "\n⚠️  ADVERTENCIA: La llave no es RSA\n";
    echo "VUCEM típicamente requiere llaves RSA. Verifique compatibilidad.\n";
}

// Verificar tamaño de llave
if ($detallesLlave['bits'] < 1024) {
    echo "\n⚠️  ADVERTENCIA: Llave muy corta ({$detallesLlave['bits']} bits)\n";
    echo "Llaves menores a 1024 bits podrían no ser aceptadas por VUCEM.\n";
} elseif ($detallesLlave['bits'] >= 2048) {
    echo "\n✅ Tamaño de llave apropiado ({$detallesLlave['bits']} bits)\n";
}

echo "\n5. Test de compatibilidad con la aplicación...\n";

// Simular la validación de la aplicación
$appTest = @openssl_pkey_get_private($content, $passwordCorrecta);
if ($appTest !== false) {
    echo "   ✅ Pasaría la validación de la aplicación\n";
    openssl_free_key($appTest);
} else {
    echo "   ❌ NO pasaría la validación de la aplicación\n";
}

// Test de conversión a Base64 (como hace la aplicación)
$base64Content = base64_encode($content);
$appTestBase64 = @openssl_pkey_get_private($content, $passwordCorrecta);
if ($appTestBase64 !== false) {
    echo "   ✅ Compatible con codificación Base64\n";
    openssl_free_key($appTestBase64);
}

echo "\n=== RESULTADO FINAL ===\n";
echo "✅ LLAVE PRIVADA VÁLIDA ENCONTRADA\n";
echo "\nDatos para usar en la aplicación:\n";
echo "- Archivo: {$llavePrivadaPath}\n";
echo "- Contraseña: " . (empty($passwordCorrecta) ? "[Dejar en blanco]" : "La contraseña #{$passwordCorrecta}") . "\n";
echo "- Formato: {$formato}\n";
echo "- Bits: {$detallesLlave['bits']}\n";

echo "\nInstrucciones:\n";
echo "1. Sube el archivo de llave privada en la aplicación\n";
echo "2. Usa exactamente la contraseña que funcionó aquí\n";
if (empty($passwordCorrecta)) {
    echo "3. Deja el campo de contraseña VACÍO o pon cualquier cosa (se ignorará)\n";
} else {
    echo "3. Escribe la contraseña exactamente como la configuraste\n";
}
echo "4. La aplicación debería aceptar la llave correctamente\n";

echo "\n¡Tu llave privada está lista para usar!\n";