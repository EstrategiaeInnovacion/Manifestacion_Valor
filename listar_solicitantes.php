<?php
require_once __DIR__ . '/vendor/autoload.php';

// Configurar ambiente de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MvClientApplicant;

echo "=== SOLICITANTES DISPONIBLES ===\n\n";

$solicitantes = MvClientApplicant::select('id', 'applicant_rfc', 'ws_file_upload_key')
    ->get();

if ($solicitantes->isEmpty()) {
    echo "❌ No hay solicitantes registrados\n";
} else {
    foreach ($solicitantes as $solicitante) {
        echo "ID: {$solicitante->id}\n";
        echo "RFC: {$solicitante->applicant_rfc}\n";
        echo "WS Key: " . (strlen($solicitante->ws_file_upload_key ?? '') > 0 ? '[CONFIGURADA]' : '[FALTANTE]') . "\n";
        echo "---\n";
    }
}

echo "\n=== CONFIGURACIÓN VUCEM ACTUAL ===\n";
echo "Endpoint: " . config('vucem.edocument.endpoint') . "\n";
echo "SOAP Action: " . config('vucem.edocument.soap_action') . "\n";