<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpCfdi\Credentials\Credential;

class EFirmaService
{
    /**
     * Genera la firma electrónica (Sello) usando phpcfdi/credentials.
     * Maneja automáticamente .key, .cer, PEM, DER y contraseñas.
     */
    public function generarFirmaElectronicaConArchivos(
        string $cadenaOriginal,
        string $rfc,
        string $certificadoPath,
        string $llavePrivadaPath,
        string $passwordLlave
    ): array {
        try {
            // 1. Cargar la Credencial (La librería hace todo el trabajo sucio de conversión)
            // openFiles detecta si es DER/PEM y valida la contraseña automáticamente.
            $credential = Credential::openFiles(
                $certificadoPath, 
                $llavePrivadaPath, 
                $passwordLlave
            );

            // (Opcional) Validar que el RFC del certificado coincida con el usuario
            $rfcCertificado = $credential->rfc();
            if ($rfc !== $rfcCertificado) {
                Log::warning("[EFirmaService] El RFC del certificado ($rfcCertificado) no coincide con el RFC del usuario ($rfc).");
            }

            // 2. Preparar Cadena Original
            // VUCEM requiere que la firma se calcule sobre la cadena en encoding ISO-8859-1
            $cadenaOriginalIso = mb_convert_encoding($cadenaOriginal, 'ISO-8859-1', 'UTF-8');

            // 3. Firmar
            // La librería usa SHA256 por defecto (OPENSSL_ALGO_SHA256)
            $binarySignature = $credential->sign($cadenaOriginalIso);

            // 4. Extraer certificado limpio para el XML
            // VUCEM necesita el certificado en Base64 SIN cabeceras (-----BEGIN...)
            $certificadoPem = $credential->certificate()->pem();
            $certificadoLimpio = $this->limpiarCertificado($certificadoPem);

            return [
                'certificado' => $certificadoLimpio,
                'cadenaOriginal' => $cadenaOriginal, // Se devuelve la original (UTF-8) para guardar en BD si es necesario
                'firma' => base64_encode($binarySignature) // La firma binaria se pasa a Base64
            ];

        } catch (Exception $e) {
            $rawMsg = $e->getMessage();
            Log::error('[EFirmaService] Error crítico generando firma', [
                'error'    => $rawMsg,
                'rfc'      => $rfc,
                'cert_exists' => file_exists($certificadoPath),
                'key_exists'  => file_exists($llavePrivadaPath),
                'pass_len'    => strlen($passwordLlave),
            ]);

            // Traducir el error de OpenSSL a un mensaje comprensible
            if (str_contains($rawMsg, 'pkcs12 cipherfinal') || str_contains($rawMsg, 'Cannot open private key')) {
                throw new Exception(
                    'La contraseña de la llave privada (.key) es incorrecta, o el archivo .key no corresponde '
                    . 'a esa contraseña. Verifique que está usando la contraseña correcta y el archivo correcto.'
                );
            }

            if (str_contains($rawMsg, 'RFC') && str_contains($rawMsg, 'certificado')) {
                throw new Exception('El certificado no corresponde al RFC ' . $rfc . '. Verifique los archivos de e.firma.');
            }

            throw new Exception('Error al procesar la e.firma: ' . $rawMsg);
        }
    }

    /**
     * Elimina cabeceras y espacios del PEM para insertarlo en el XML de VUCEM.
     */
    private function limpiarCertificado(string $pem): string
    {
        return trim(str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", " "], 
            '', 
            $pem
        ));
    }
}