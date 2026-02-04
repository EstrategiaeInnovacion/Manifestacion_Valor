<?php
// public/prueba_netxico.php

// 1. Cargar el entorno de Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\ConsultarEdocumentService;
use App\Services\EFirmaService;

// Limpiamos la pantalla
if (php_sapi_name() !== 'cli') echo "<pre>";
echo "<h1>🚀 Prueba de Consulta Real: NETXICO</h1>";

// 2. DATOS DEL ARCHIVO M (Registro 501 y 507)
$rfc = 'NET070608EM9';
// Probamos con el primer eDocument del archivo M
$eDocument = '043825149DMT6'; 

echo "🔹 RFC: $rfc\n";
echo "🔹 eDocument: $eDocument\n";

// 3. RUTAS DE TUS ARCHIVOS
// Usamos la configuración de config/vucem.php para no fallar
$basePath = storage_path('../' . config('vucem.efirma.path') . '/'); 

$certPath = $basePath . config('vucem.efirma.cert_file');
$keyPath  = $basePath . config('vucem.efirma.key_file'); // Ahora leerá LLAVE_NUEVA1_PEM.key
$passPath = $basePath . config('vucem.efirma.password_file');

echo "🔹 Llave: " . basename($keyPath) . "\n";

// Validar que existan
if (!file_exists($certPath)) die("❌ ERROR: No encuentro el certificado .cer en: $certPath");
if (!file_exists($keyPath))  die("❌ ERROR: No encuentro la llave .key en: $keyPath");
if (!file_exists($passPath)) die("❌ ERROR: No encuentro CONTRASEÑA.txt en: $passPath");

// 4. PREPARAR CONTRASEÑAS
// Contraseña del Web Service (Login): Viene del archivo TXT
$claveWebService = trim(file_get_contents($passPath));

// Contraseña de la Llave Privada (Firma): VACÍA porque es PEM sin encriptar
$passwordLlave = ''; 

echo "🔹 Password Llave: [VACÍA] (Correcto para PEM)\n";
echo "🔹 Password WS: [OK] (Leída del TXT)\n";

// 5. EJECUTAR CONSULTA
try {
    $service = new ConsultarEdocumentService(new EFirmaService());
    
    echo "\n📡 Conectando a VUCEM Producción...\n";

    $resultado = $service->consultarEdocument(
        $eDocument,
        $rfc,
        $claveWebService, // Autenticación (Login)
        $certPath,
        $keyPath,
        $passwordLlave    // Desencriptado de llave (Vacío)
    );

    // 6. MOSTRAR RESULTADO
    print_r($resultado);

    if ($resultado['success']) {
        echo "\n🎉 ¡ÉXITO! Conexión, Firma y Permisos Validados.\n";
        echo "📂 Tienes datos del COVE disponibles.\n";
    } else {
        echo "\n❌ ERROR DE VUCEM:\n" . $resultado['message'] . "\n";
    }

} catch (Exception $e) {
    echo "❌ EXCEPCIÓN DEL SISTEMA: " . $e->getMessage();
}