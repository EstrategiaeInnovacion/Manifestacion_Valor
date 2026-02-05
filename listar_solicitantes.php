<?php
require_once __DIR__ . '/vendor/autoload.php';

// Configurar ambiente de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MvClientApplicant;

echo "=== SOLICITANTES DISPONIBLES ===\n\n";

$solicitantes = MvClientApplicant::select('id', 'applicant_rfc', 'business_name')
    ->get();

if ($solicitantes->isEmpty()) {
    echo "❌ No hay solicitantes registrados\n";
} else {
    foreach ($solicitantes as $solicitante) {
        echo "ID: {$solicitante->id}\n";
        echo "RFC: {$solicitante->applicant_rfc}\n";
        echo "Razón Social: {$solicitante->business_name}\n";
        echo "---\n";
    }
}

echo "\n=== CONFIGURACIÓN VUCEM ACTUAL ===\n";
echo "Endpoint: " . config('vucem.edocument.endpoint') . "\n";
echo "SOAP Action: " . config('vucem.edocument.soap_action') . "\n";