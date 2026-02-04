<?php
/**
 * Script para revisar logs de eDocument de forma más legible
 * 
 * Ejecutar: php revisar_logs_edocument.php
 */

$logDir = __DIR__ . '/storage/logs';
$logFiles = glob($logDir . '/laravel-*.log');

if (empty($logFiles)) {
    echo "No se encontraron archivos de log.\n";
    exit(1);
}

// Obtener el log más reciente
rsort($logFiles);
$latestLog = $logFiles[0];

echo "═══════════════════════════════════════════════════════════════\n";
echo "  LOGS DE EDOCUMENT - " . basename($latestLog) . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$lines = file($latestLog);
$inEdocumentSection = false;
$buffer = [];

foreach ($lines as $line) {
    // Detectar inicio de sección EDOCUMENT
    if (stripos($line, '[EDOCUMENT') !== false) {
        $inEdocumentSection = true;
        $buffer = [];
    }
    
    // Si estamos en una sección EDOCUMENT, agregar líneas
    if ($inEdocumentSection) {
        $buffer[] = $line;
        
        // Detectar fin de sección (línea que no es continuación)
        if (!preg_match('/^\s+/', trim($line)) && count($buffer) > 1) {
            // Si la siguiente línea no es EDOCUMENT, imprimir buffer
            $nextIsEdocument = false;
        }
    }
    
    // Si encontramos una línea separadora o inicio de nueva sección
    if (stripos($line, '=====') !== false && $inEdocumentSection) {
        echo implode('', $buffer);
        echo "\n";
        $buffer = [];
    }
}

// Imprimir buffer final si queda algo
if (!empty($buffer)) {
    echo implode('', $buffer);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  FIN DE LOGS\n";
echo "═══════════════════════════════════════════════════════════════\n";
