<?php
require_once __DIR__ . '/vendor/autoload.php';

// Script para debuggear archivos de eFirma del SAT
class DebugEFirma
{
    public static function analizarArchivos($certificadoPath, $llavePrivadaPath, $password)
    {
        echo "=== ANÁLISIS DE ARCHIVOS DE E-FIRMA ===\n\n";
        
        // Analizar certificado
        echo "📄 CERTIFICADO (.cer): {$certificadoPath}\n";
        if (!file_exists($certificadoPath)) {
            echo "❌ Archivo no encontrado\n\n";
        } else {
            $certContent = file_get_contents($certificadoPath);
            echo "📏 Tamaño: " . strlen($certContent) . " bytes\n";
            echo "🔍 Primeros bytes: " . bin2hex(substr($certContent, 0, 10)) . "\n";
            echo "📋 Formato detectado: " . (strpos($certContent, '-----BEGIN') !== false ? 'PEM' : 'DER') . "\n";
            
            // Intentar parsear certificado
            $x509 = openssl_x509_parse($certContent);
            if ($x509) {
                echo "✅ Certificado válido\n";
                echo "👤 Subject: " . $x509['subject']['CN'] . "\n";
                echo "📅 Valid from: " . date('Y-m-d H:i:s', $x509['validFrom_time_t']) . "\n";
                echo "📅 Valid to: " . date('Y-m-d H:i:s', $x509['validTo_time_t']) . "\n";
            } else {
                echo "❌ Error al parsear certificado: " . openssl_error_string() . "\n";
            }
            echo "\n";
        }
        
        // Analizar llave privada
        echo "🔐 LLAVE PRIVADA (.key): {$llavePrivadaPath}\n";
        if (!file_exists($llavePrivadaPath)) {
            echo "❌ Archivo no encontrado\n\n";
        } else {
            $keyContent = file_get_contents($llavePrivadaPath);
            echo "📏 Tamaño: " . strlen($keyContent) . " bytes\n";
            echo "🔍 Primeros bytes: " . bin2hex(substr($keyContent, 0, 10)) . "\n";
            echo "📋 Formato detectado: " . (strpos($keyContent, '-----BEGIN') !== false ? 'PEM' : 'DER') . "\n";
            
            // Probar diferentes estrategias
            echo "\n🧪 PROBANDO ESTRATEGIAS DE CARGA:\n";
            
            // Estrategia 1: Cargar directamente
            echo "1️⃣  Carga directa con contraseña:\n";
            $pkey = @openssl_pkey_get_private($keyContent, $password);
            if ($pkey) {
                echo "   ✅ ÉXITO\n";
                openssl_free_key($pkey);
            } else {
                echo "   ❌ Error: " . openssl_error_string() . "\n";
            }
            
            // Estrategia 2: Cargar sin contraseña
            echo "2️⃣  Carga directa sin contraseña:\n";
            $pkey = @openssl_pkey_get_private($keyContent);
            if ($pkey) {
                echo "   ✅ ÉXITO\n";
                openssl_free_key($pkey);
            } else {
                echo "   ❌ Error: " . openssl_error_string() . "\n";
            }
            
            // Estrategia 3: Convertir DER a PEM si es necesario
            if (strpos($keyContent, '-----BEGIN') === false) {
                echo "3️⃣  Conversión DER a PEM (PRIVATE KEY):\n";
                $pemKey = "-----BEGIN PRIVATE KEY-----\n";
                $pemKey .= chunk_split(base64_encode($keyContent), 64, "\n");
                $pemKey .= "-----END PRIVATE KEY-----\n";
                
                $pkey = @openssl_pkey_get_private($pemKey, $password);
                if ($pkey) {
                    echo "   ✅ ÉXITO\n";
                    openssl_free_key($pkey);
                } else {
                    echo "   ❌ Error: " . openssl_error_string() . "\n";
                }
                
                echo "4️⃣  Conversión DER a PEM (RSA PRIVATE KEY):\n";
                $rsaPemKey = "-----BEGIN RSA PRIVATE KEY-----\n";
                $rsaPemKey .= chunk_split(base64_encode($keyContent), 64, "\n");
                $rsaPemKey .= "-----END RSA PRIVATE KEY-----\n";
                
                $pkey = @openssl_pkey_get_private($rsaPemKey, $password);
                if ($pkey) {
                    echo "   ✅ ÉXITO\n";
                    openssl_free_key($pkey);
                } else {
                    echo "   ❌ Error: " . openssl_error_string() . "\n";
                }
            }
            
            // Estrategia 5: Análisis de contenido binario
            echo "5️⃣  Análisis de contenido binario:\n";
            $nonPrintable = 0;
            $length = min(strlen($keyContent), 200);
            
            for ($i = 0; $i < $length; $i++) {
                $byte = ord($keyContent[$i]);
                if ($byte < 32 && !in_array($byte, [9, 10, 13], true)) {
                    $nonPrintable++;
                } elseif ($byte > 126) {
                    $nonPrintable++;
                }
            }
            
            $binaryPercent = ($nonPrintable / $length) * 100;
            echo "   📊 Contenido binario: {$binaryPercent}%\n";
            echo "   🎯 Es binario: " . ($binaryPercent > 30 ? 'SÍ' : 'NO') . "\n";
        }
        
        echo "\n=== PRUEBA DE FIRMA ===\n";
        
        if (file_exists($llavePrivadaPath)) {
            $keyContent = file_get_contents($llavePrivadaPath);
            $cadenaTest = "Esta es una cadena de prueba para firmar";
            
            // Probar todas las estrategias exitosas
            $strategies = [
                'original' => $keyContent,
            ];
            
            if (strpos($keyContent, '-----BEGIN') === false) {
                $strategies['pem_private'] = "-----BEGIN PRIVATE KEY-----\n" . 
                    chunk_split(base64_encode($keyContent), 64, "\n") . 
                    "-----END PRIVATE KEY-----\n";
                    
                $strategies['pem_rsa'] = "-----BEGIN RSA PRIVATE KEY-----\n" . 
                    chunk_split(base64_encode($keyContent), 64, "\n") . 
                    "-----END RSA PRIVATE KEY-----\n";
            }
            
            foreach ($strategies as $name => $content) {
                echo "\n🔑 Probando firma con estrategia: {$name}\n";
                
                $pkey = @openssl_pkey_get_private($content, $password);
                if (!$pkey) {
                    $pkey = @openssl_pkey_get_private($content);
                }
                
                if ($pkey) {
                    $signature = '';
                    $success = openssl_sign($cadenaTest, $signature, $pkey, OPENSSL_ALGO_SHA256);
                    openssl_free_key($pkey);
                    
                    if ($success) {
                        echo "   ✅ FIRMA EXITOSA\n";
                        echo "   📝 Firma (base64): " . substr(base64_encode($signature), 0, 50) . "...\n";
                        break;
                    } else {
                        echo "   ❌ Error al firmar: " . openssl_error_string() . "\n";
                    }
                } else {
                    echo "   ❌ No se pudo cargar la llave\n";
                }
            }
        }
        
        echo "\n=== FIN DEL ANÁLISIS ===\n";
    }
}

// Usar el script
if ($argc < 4) {
    echo "Uso: php debug_efirma_completo.php <certificado.cer> <llave.key> <contraseña>\n";
    echo "Ejemplo: php debug_efirma_completo.php certificado.cer llave.key micontraseña\n";
    exit(1);
}

$certificadoPath = $argv[1];
$llavePrivadaPath = $argv[2];
$password = $argv[3];

DebugEFirma::analizarArchivos($certificadoPath, $llavePrivadaPath, $password);