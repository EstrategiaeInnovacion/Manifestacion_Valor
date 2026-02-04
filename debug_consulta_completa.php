<?php
require_once __DIR__ . '/vendor/autoload.php';

// Configurar ambiente de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ConsultarEdocumentService;
use App\Services\ManifestacionValorService;

// Mock simple para Log
class MockLog {
    public static function info($message, $context = []) {
        echo "[INFO] $message\n";
        if (!empty($context)) echo "      " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    public static function warning($message, $context = []) {
        echo "[WARN] $message\n";
        if (!empty($context)) echo "      " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    public static function error($message, $context = []) {
        echo "[ERROR] $message\n";
        if (!empty($context)) echo "      " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    public static function debug($message, $context = []) {
        echo "[DEBUG] $message\n";
        if (!empty($context)) echo "       " . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
    public static function channel($channel) {
        return new static();
    }
}

// Simular consulta completa
function simularConsultaCompleta($folio, $certificadoPath, $llavePrivadaPath, $password) {
    echo "=== SIMULACIÓN DE CONSULTA EDOCUMENT COMPLETA ===\n\n";
    
    try {
        echo "📋 PARÁMETROS DE ENTRADA:\n";
        echo "   🎯 Folio: $folio\n";
        echo "   📄 Certificado: $certificadoPath\n";
        echo "   🔐 Llave privada: $llavePrivadaPath\n";
        echo "   🔒 Contraseña: [OCULTA]\n\n";
        
        // 1. Validar folio
        echo "1️⃣ VALIDANDO FOLIO...\n";
        $manifestacionService = new ManifestacionValorService();
        $folioNormalizado = $manifestacionService->normalizeEdocumentFolio($folio);
        echo "   📝 Folio normalizado: '$folioNormalizado'\n";
        
        $validacion = $manifestacionService->validateEdocumentFolio($folioNormalizado);
        if (!$validacion['valid']) {
            throw new Exception("Folio inválido: " . $validacion['message']);
        }
        echo "   ✅ Folio válido\n\n";
        
        // 2. Verificar archivos
        echo "2️⃣ VERIFICANDO ARCHIVOS...\n";
        if (!file_exists($certificadoPath)) {
            throw new Exception("Certificado no encontrado: $certificadoPath");
        }
        if (!file_exists($llavePrivadaPath)) {
            throw new Exception("Llave privada no encontrada: $llavePrivadaPath");
        }
        
        $certSize = filesize($certificadoPath);
        $keySize = filesize($llavePrivadaPath);
        echo "   📄 Certificado: $certSize bytes\n";
        echo "   🔐 Llave privada: $keySize bytes\n";
        echo "   ✅ Archivos encontrados\n\n";
        
        // 3. Datos de prueba del solicitante
        echo "3️⃣ CONFIGURANDO CREDENCIALES DE PRUEBA...\n";
        $rfc = 'NET070608EM9'; // RFC de ejemplo de los archivos de prueba
        $claveWebService = 'CLAVE_WEBSERVICE_PRUEBA'; // Clave de webservice de ejemplo
        echo "   👤 RFC: $rfc\n";
        echo "   🔑 Clave WS: [OCULTA]\n\n";
        
        // 4. Simular procesamiento de archivos temporales
        echo "4️⃣ PROCESANDO ARCHIVOS TEMPORALES...\n";
        $tempCertPath = tempnam(sys_get_temp_dir(), 'cert_');
        $tempKeyPath = tempnam(sys_get_temp_dir(), 'key_');
        
        $certContent = file_get_contents($certificadoPath);
        $keyContent = file_get_contents($llavePrivadaPath);
        
        file_put_contents($tempCertPath, $certContent);
        file_put_contents($tempKeyPath, $keyContent);
        
        echo "   📁 Archivos temporales creados\n";
        echo "   🔍 Cert primeros bytes: " . bin2hex(substr($certContent, 0, 10)) . "\n";
        echo "   🔍 Key primeros bytes: " . bin2hex(substr($keyContent, 0, 10)) . "\n";
        echo "   ✅ Archivos procesados\n\n";
        
        // 5. Inicializar servicio de consulta
        echo "5️⃣ INICIANDO SERVICIO DE CONSULTA...\n";
        
        // Redirigir Log a nuestro mock
        app()->bind('log', function() {
            return new MockLog();
        });
        
        $consultarService = new ConsultarEdocumentService();
        echo "   ✅ Servicio inicializado\n\n";
        
        // 6. Realizar consulta
        echo "6️⃣ EJECUTANDO CONSULTA VUCEM...\n";
        $resultado = $consultarService->consultarEdocument(
            $folioNormalizado,
            $rfc,
            $claveWebService,
            $tempCertPath,
            $tempKeyPath,
            $password
        );
        
        echo "\n7️⃣ RESULTADO DE LA CONSULTA:\n";
        echo "   🎯 Éxito: " . ($resultado['success'] ? 'SÍ' : 'NO') . "\n";
        echo "   💬 Mensaje: " . ($resultado['message'] ?? 'Sin mensaje') . "\n";
        
        if (!$resultado['success']) {
            echo "   ❌ Tipo de error: " . ($resultado['error_type'] ?? 'desconocido') . "\n";
        } else {
            echo "   ✅ Consulta exitosa\n";
            if (isset($resultado['cove_data'])) {
                echo "   📊 Datos COVE recibidos\n";
            }
        }
        
        // Limpiar archivos temporales
        @unlink($tempCertPath);
        @unlink($tempKeyPath);
        
        return $resultado;
        
    } catch (\Exception $e) {
        echo "\n❌ ERROR CAPTURADO:\n";
        echo "   📍 Mensaje: " . $e->getMessage() . "\n";
        echo "   📁 Archivo: " . $e->getFile() . "\n";
        echo "   📏 Línea: " . $e->getLine() . "\n";
        echo "   🔍 Stack trace:\n" . $e->getTraceAsString() . "\n";
        
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'error_type' => 'exception'
        ];
    } finally {
        // Limpiar archivos temporales si existen
        if (isset($tempCertPath) && file_exists($tempCertPath)) {
            @unlink($tempCertPath);
        }
        if (isset($tempKeyPath) && file_exists($tempKeyPath)) {
            @unlink($tempKeyPath);
        }
    }
}

// Verificar argumentos
if ($argc < 4) {
    echo "Uso: php debug_consulta_completa.php <folio> <certificado.cer> <llave.key> <contraseña>\n";
    echo "Ejemplo: php debug_consulta_completa.php 04382519SEDK2 pruebaEfirma\\00001000000716248795.cer pruebaEfirma\\Claveprivada_FIEL_NET070608EM9_20250604_163343.key NetxicoEM9\n";
    exit(1);
}

$folio = $argv[1];
$certificadoPath = $argv[2];
$llavePrivadaPath = $argv[3];
$password = $argv[4];

$resultado = simularConsultaCompleta($folio, $certificadoPath, $llavePrivadaPath, $password);

echo "\n=== RESULTADO FINAL ===\n";
if ($resultado['success']) {
    echo "✅ CONSULTA EXITOSA\n";
} else {
    echo "❌ CONSULTA FALLIDA: " . $resultado['message'] . "\n";
}
echo "============================\n";