<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MvClientApplicant;
use App\Services\EFirmaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Test para consultar el resultado de una operación de digitalización en VUCEM.
 * 
 * Basado en el WSDL real descargado de:
 * https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService?wsdl
 * 
 * Operación: ConsultaEDocumentDigitalizarDocumento
 * Elemento:  consultaDigitalizarDocumentoServiceRequest
 * Namespace: http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/DigitalizarDocumento
 * SOAPAction: http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento
 * Requiere:  numeroOperacion + peticionBase(firmaElectronica: certificado, cadenaOriginal, firma)
 * Respuesta: eDocument, numeroDeTramite, cadenaOriginal, respuestaBase(tieneError, error)
 */
class TestConsultaDigitalizacion extends Command
{
    protected $signature = 'vucem:test-consulta-digitalizacion 
                            {operacion : Número de operación VUCEM (ej: 313962613)} 
                            {--applicant=1 : ID del applicant en BD}
                            {--raw : Mostrar XML crudo de request y response}';

    protected $description = 'Consulta el resultado de una operación de digitalización en VUCEM usando el elemento correcto del WSDL';

    private const NS_DIG = 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/DigitalizarDocumento';
    private const NS_RES = 'http://www.ventanillaunica.gob.mx/common/ws/oxml/respuesta';
    private const SOAP_ACTION = 'http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento';
    private const ENDPOINT = 'https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService';

    public function handle(): int
    {
        $operacion = trim($this->argument('operacion'));
        $applicantId = (int) $this->option('applicant');
        $showRaw = $this->option('raw');

        $this->info('╔════════════════════════════════════════════════════════════════════╗');
        $this->info('║  Consulta eDocument por Operación (WSDL: DigitalizarDocumento)    ║');
        $this->info('╚════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("Operación: <fg=yellow>{$operacion}</>");
        $this->line("Endpoint: <fg=gray>" . self::ENDPOINT . "</>");
        $this->line("SOAPAction: <fg=gray>" . self::SOAP_ACTION . "</>");
        $this->line("Elemento: <fg=gray>consultaDigitalizarDocumentoServiceRequest</>");
        $this->newLine();

        // ─── 1. Obtener credenciales del applicant ───
        $this->info('── 1. Obteniendo credenciales de BD ──');

        $applicant = MvClientApplicant::find($applicantId);
        if (!$applicant) {
            $this->error("Applicant ID {$applicantId} no encontrado.");
            return self::FAILURE;
        }

        $rfc = strtoupper(trim($applicant->applicant_rfc));
        $claveWs = $applicant->vucem_webservice_key;

        $this->line("RFC: <fg=green>{$rfc}</>");
        $this->line("Clave WS: <fg=green>" . substr($claveWs, 0, 6) . '***' . substr($claveWs, -4) . "</> (" . strlen($claveWs) . " chars)");

        // Verificar que tiene certificado y llave almacenados
        if (!$applicant->hasVucemCredentials()) {
            $this->error('El applicant no tiene certificado/llave VUCEM almacenados.');
            $this->line('Cert: ' . (empty($applicant->vucem_cert_file) ? '<fg=red>VACÍO</>' : '<fg=green>OK</>'));
            $this->line('Key: ' . (empty($applicant->vucem_key_file) ? '<fg=red>VACÍO</>' : '<fg=green>OK</>'));
            $this->line('Password: ' . (empty($applicant->vucem_password) ? '<fg=red>VACÍO</>' : '<fg=green>OK</>'));
            return self::FAILURE;
        }
        $this->line("Certificado e.firma: <fg=green>OK</>");
        $this->line("Llave privada: <fg=green>OK</>");
        $this->newLine();

        // ─── 2. Preparar archivos temporales de firma ───
        $this->info('── 2. Preparando e.firma ──');

        $tempCertPath = tempnam(sys_get_temp_dir(), 'vucem_cert_');
        $tempKeyPath = tempnam(sys_get_temp_dir(), 'vucem_key_');

        try {
            $certContent = base64_decode($applicant->vucem_cert_file);
            $keyContent = base64_decode($applicant->vucem_key_file);
            $passwordLlave = $applicant->vucem_password;

            if (!$certContent || !$keyContent || !$passwordLlave) {
                $this->error('No se pudieron decodificar las credenciales almacenadas.');
                return self::FAILURE;
            }

            file_put_contents($tempCertPath, $certContent);
            file_put_contents($tempKeyPath, $keyContent);

            $this->line("Cert temp: <fg=gray>{$tempCertPath}</> (" . strlen($certContent) . " bytes)");
            $this->line("Key temp: <fg=gray>{$tempKeyPath}</> (" . strlen($keyContent) . " bytes)");

            // ─── 3. Generar firma electrónica ───
            $this->info('── 3. Generando firma electrónica ──');

            // Cadena original para consulta de digitalización: |RFC|numeroOperacion|
            $cadenaOriginal = '|' . $rfc . '|' . $operacion . '|';
            $this->line("Cadena original: <fg=cyan>{$cadenaOriginal}</>");

            $efirmaService = app(EFirmaService::class);
            $firma = $efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal,
                $rfc,
                $tempCertPath,
                $tempKeyPath,
                $passwordLlave
            );

            $this->line("Certificado: <fg=green>" . substr($firma['certificado'], 0, 40) . "...</> (" . strlen($firma['certificado']) . " chars)");
            $this->line("Firma: <fg=green>" . substr($firma['firma'], 0, 40) . "...</> (" . strlen($firma['firma']) . " chars)");
            $this->newLine();

            // ─── 4. Construir XML SOAP ───
            $this->info('── 4. Construyendo XML SOAP ──');

            $created = gmdate("Y-m-d\TH:i:s\Z");
            $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

            $rfcXml = htmlspecialchars($rfc, ENT_XML1, 'UTF-8');
            $claveXml = htmlspecialchars($claveWs, ENT_XML1, 'UTF-8');

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="' . self::NS_DIG . '" xmlns:res="' . self::NS_RES . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsu:Timestamp wsu:Id="TS-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . $rfcXml . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $claveXml . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:consultaDigitalizarDocumentoServiceRequest>
         <dig:numeroOperacion>' . $operacion . '</dig:numeroOperacion>
         <dig:peticionBase>
            <res:firmaElectronica>
               <res:certificado>' . $firma['certificado'] . '</res:certificado>
               <res:cadenaOriginal>' . $firma['cadenaOriginal'] . '</res:cadenaOriginal>
               <res:firma>' . $firma['firma'] . '</res:firma>
            </res:firmaElectronica>
         </dig:peticionBase>
      </dig:consultaDigitalizarDocumentoServiceRequest>
   </soapenv:Body>
</soapenv:Envelope>';

            $this->line("XML Length: <fg=gray>" . strlen($xml) . " bytes</>");

            if ($showRaw) {
                $this->newLine();
                $this->info('── XML Request ──');
                // Mostrar sin certificado completo para que sea legible
                $xmlPreview = preg_replace('/<res:certificado>.*?<\/res:certificado>/s', '<res:certificado>[CERT_OMITIDO]</res:certificado>', $xml);
                $xmlPreview = preg_replace('/<res:firma>.*?<\/res:firma>/s', '<res:firma>[FIRMA_OMITIDA]</res:firma>', $xmlPreview);
                $this->line($xmlPreview);
            }
            $this->newLine();

            // ─── 5. Enviar petición ───
            $this->info('── 5. Enviando petición a VUCEM ──');

            $ch = curl_init(self::ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ' . self::SOAP_ACTION,
                    'Content-Length: ' . strlen($xml),
                ],
                CURLOPT_HEADER => true,
            ]);

            $fullResponse = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->error("Error cURL: {$error}");
                return self::FAILURE;
            }

            $responseBody = substr($fullResponse, $headerSize);

            $httpColor = ($httpCode === 200) ? 'green' : (($httpCode === 500) ? 'red' : 'yellow');
            $this->line("HTTP Status: <fg={$httpColor}>{$httpCode}</>");
            $this->line("Tiempo: <fg=gray>{$totalTime}s</>");
            $this->line("Tamaño respuesta: <fg=gray>" . strlen($responseBody) . " bytes</>");

            // Extraer XML de MTOM
            $xmlBody = $responseBody;
            if (preg_match('/<\?xml.*?\?>.+<\/S:Envelope>/s', $responseBody, $match)) {
                $xmlBody = $match[0];
            } elseif (preg_match('/<S:Envelope.*?<\/S:Envelope>/s', $responseBody, $match)) {
                $xmlBody = $match[0];
            }

            if ($showRaw) {
                $this->newLine();
                $this->info('── XML Response ──');
                $this->line($xmlBody);
            }
            $this->newLine();

            // ─── 6. Analizar respuesta ───
            $this->info('── 6. Análisis de respuesta ──');

            // SOAP Fault
            if (preg_match('/<[:\w]*faultstring>(.*?)<\/[:\w]*faultstring>/s', $xmlBody, $fMatch)) {
                $this->error("SOAP FAULT: " . trim($fMatch[1]));
                if (preg_match('/<[:\w]*faultcode>(.*?)<\/[:\w]*faultcode>/s', $xmlBody, $codeMatch)) {
                    $this->line("Fault Code: <fg=red>" . trim($codeMatch[1]) . "</>");
                }
                
                if (str_contains($fMatch[1], 'Cannot find dispatch method')) {
                    $this->newLine();
                    $this->error('El elemento XML no fue reconocido por VUCEM.');
                }
                return self::FAILURE;
            }

            // tieneError
            $tieneError = false;
            if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/s', $xmlBody, $errMatch)) {
                $tieneError = filter_var(trim($errMatch[1]), FILTER_VALIDATE_BOOLEAN);
                $errColor = $tieneError ? 'red' : 'green';
                $this->line("tieneError: <fg={$errColor}>" . trim($errMatch[1]) . "</>");
            }

            // Mensajes de error
            if (preg_match_all('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/s', $xmlBody, $allMsgs)) {
                foreach ($allMsgs[1] as $i => $msg) {
                    $msgClean = trim(strip_tags($msg));
                    if (!empty($msgClean)) {
                        $this->line("mensaje[{$i}]: <fg=yellow>{$msgClean}</>");
                    }
                }
            }

            if ($tieneError) {
                $this->newLine();
                $this->error('═══ VUCEM reportó error ═══');
                return self::FAILURE;
            }

            // ─── Buscar eDocument (campo principal de la respuesta) ───
            if (preg_match('/<[:\w]*eDocument>(.*?)<\/[:\w]*eDocument>/s', $xmlBody, $edocMatch)) {
                $eDocument = trim($edocMatch[1]);
                $this->newLine();
                $this->info("════════════════════════════════════════════");
                $this->info("  ¡ÉXITO! eDocument: {$eDocument}");
                $this->info("════════════════════════════════════════════");

                // numeroDeTramite
                if (preg_match('/<[:\w]*numeroDeTramite>(.*?)<\/[:\w]*numeroDeTramite>/s', $xmlBody, $tramMatch)) {
                    $this->line("  numeroDeTramite: <fg=cyan>" . trim($tramMatch[1]) . "</>");
                }

                // cadenaOriginal de respuesta
                if (preg_match('/<[:\w]*cadenaOriginal>(.*?)<\/[:\w]*cadenaOriginal>/s', $xmlBody, $coMatch)) {
                    $this->line("  cadenaOriginal: <fg=gray>" . trim($coMatch[1]) . "</>");
                }

                Log::info('[TEST_CONSULTA_DIG] eDocument obtenido', [
                    'operacion' => $operacion,
                    'eDocument' => $eDocument,
                    'applicant_id' => $applicantId,
                ]);
            } else {
                $this->newLine();
                $this->warn('No se encontró eDocument en la respuesta.');
                $this->line('Posiblemente VUCEM aún está procesando la operación.');
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Excepción: " . $e->getMessage());
            Log::error('[TEST_CONSULTA_DIG] Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return self::FAILURE;
        } finally {
            // Limpiar archivos temporales
            if (file_exists($tempCertPath)) @unlink($tempCertPath);
            if (file_exists($tempKeyPath)) @unlink($tempKeyPath);
        }
    }
}
