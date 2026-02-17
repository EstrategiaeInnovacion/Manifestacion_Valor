<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MvClientApplicant;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Test para consultar un documento digitalizado en VUCEM
 * usando el servicio ConsultaAcusesServiceWS (operación consultarAcuseEdocument).
 * 
 * Usa las credenciales almacenadas en la BD del applicant indicado.
 * 
 * WSDL: https://www.ventanillaunica.gob.mx:443/ventanilla-acuses-HA/ConsultaAcusesServiceWS?wsdl
 * Endpoint: https://www.ventanillaunica.gob.mx:8107/ventanilla-acuses-HA/ConsultaAcusesServiceWS
 * Operación: consultarAcuseEdocument
 * SOAPAction: http://www.ventanillaunica.gob.mx/ventanilla/ConsultaAcusesService/consultarAcuseEdocument
 * 
 * Namespace petición: http://www.ventanillaunica.gob.mx/consulta/acuses/oxml
 * Elemento: consultaAcusesPeticion -> idEdocument
 */
class TestConsultaAcuseEdocument extends Command
{
    protected $signature = 'vucem:test-acuse-edocument 
                            {folio : Folio de operación o eDocument a consultar} 
                            {--applicant=1 : ID del applicant en BD para obtener credenciales}
                            {--endpoint= : Override del endpoint (por defecto usa el de config/con puerto 8107)}
                            {--save-pdf : Guardar el PDF del acuse si se obtiene}
                            {--raw : Mostrar respuesta XML cruda completa}';

    protected $description = 'Test de consulta de acuse de documento digitalizado en VUCEM (ConsultaAcusesServiceWS)';

    // Endpoints conocidos para el servicio de consulta de acuses
    private const ENDPOINTS = [
        'sin_puerto' => 'https://www.ventanillaunica.gob.mx/ventanilla-acuses-HA/ConsultaAcusesServiceWS',
        'puerto_8107' => 'https://www.ventanillaunica.gob.mx:8107/ventanilla-acuses-HA/ConsultaAcusesServiceWS',
        'puerto_443' => 'https://www.ventanillaunica.gob.mx:443/ventanilla-acuses-HA/ConsultaAcusesServiceWS',
    ];

    private const SOAP_ACTION = 'http://www.ventanillaunica.gob.mx/ventanilla/ConsultaAcusesService/consultarAcuseEdocument';
    private const NS_OXML = 'http://www.ventanillaunica.gob.mx/consulta/acuses/oxml';

    public function handle(): int
    {
        $folio = trim($this->argument('folio'));
        $applicantId = (int) $this->option('applicant');
        $endpointOverride = $this->option('endpoint');
        $savePdf = $this->option('save-pdf');
        $showRaw = $this->option('raw');

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║   TEST: Consulta Acuse eDocument Digitalizado (VUCEM)       ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("Folio a consultar: <fg=yellow>{$folio}</>");
        $this->line("Applicant ID: <fg=yellow>{$applicantId}</>");
        $this->newLine();

        // ─── 1. Obtener credenciales del applicant ───
        $this->info('── 1. Obteniendo credenciales de BD ──');
        
        try {
            $applicant = MvClientApplicant::find($applicantId);
        } catch (Exception $e) {
            $this->error('Error al conectar con BD: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$applicant) {
            $this->error("Applicant con ID {$applicantId} no encontrado en la base de datos.");
            return self::FAILURE;
        }

        $rfc = $applicant->applicant_rfc;
        $claveWebService = $applicant->vucem_webservice_key;

        if (empty($rfc) || empty($claveWebService)) {
            $this->error('El applicant no tiene RFC o clave de webservice configurados.');
            $this->line('RFC: ' . (empty($rfc) ? '<fg=red>VACÍO</>' : '<fg=green>OK</>'));
            $this->line('Clave WS: ' . (empty($claveWebService) ? '<fg=red>VACÍO</>' : '<fg=green>OK</>'));
            return self::FAILURE;
        }

        $this->line('RFC: <fg=green>' . $rfc . '</>');
        $this->line('Clave WS: <fg=green>' . substr($claveWebService, 0, 6) . '***' . substr($claveWebService, -4) . '</> (' . strlen($claveWebService) . ' chars)');
        $this->line('Negocio: <fg=cyan>' . ($applicant->business_name ?? 'N/A') . '</>');
        $this->newLine();

        // ─── 2. Construir XML SOAP ───
        $this->info('── 2. Construyendo XML SOAP ──');
        
        $rfcClean = strtoupper(trim($rfc));
        $folioClean = htmlspecialchars(trim($folio), ENT_XML1, 'UTF-8');
        $claveXml = htmlspecialchars($claveWebService, ENT_XML1, 'UTF-8');

        // Timestamp WS-Security
        $created = gmdate("Y-m-d\TH:i:s\Z");
        $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:oxml="' . self::NS_OXML . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" 
                     xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                     xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsu:Timestamp wsu:Id="TS-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . $rfcClean . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $claveXml . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <oxml:consultaAcusesPeticion>
         <idEdocument>' . $folioClean . '</idEdocument>
      </oxml:consultaAcusesPeticion>
   </soapenv:Body>
</soapenv:Envelope>';

        $this->line('Namespace: <fg=cyan>' . self::NS_OXML . '</>');
        $this->line('SOAPAction: <fg=cyan>' . self::SOAP_ACTION . '</>');
        $this->line('Elemento: <fg=cyan>consultaAcusesPeticion -> idEdocument=' . $folioClean . '</>');
        $this->line('Timestamp Created: <fg=gray>' . $created . '</>');
        $this->line('Timestamp Expires: <fg=gray>' . $expires . '</>');
        $this->line('XML Length: <fg=gray>' . strlen($xml) . ' bytes</>');
        $this->newLine();

        // ─── 3. Enviar petición a cada endpoint ───
        if ($endpointOverride) {
            $endpoints = ['custom' => $endpointOverride];
        } else {
            $endpoints = self::ENDPOINTS;
        }

        foreach ($endpoints as $label => $endpoint) {
            $this->info("── 3. Enviando a endpoint: {$label} ──");
            $this->line("URL: <fg=yellow>{$endpoint}</>");
            $this->newLine();

            $result = $this->sendSoapRequest($endpoint, $xml, $showRaw);

            if ($result === null) {
                $this->newLine();
                continue; // Probar siguiente endpoint
            }

            // ─── 4. Analizar respuesta ───
            $this->info('── 4. Análisis de respuesta ──');
            $this->analyzeResponse($result['body'], $folio, $savePdf);

            // Log para referencia
            Log::info('[TEST_ACUSE_EDOC] Consulta realizada', [
                'folio' => $folio,
                'applicant_id' => $applicantId,
                'rfc' => $rfcClean,
                'endpoint' => $endpoint,
                'http_code' => $result['http_code'],
                'response_length' => strlen($result['body']),
            ]);

            // Si el primer endpoint respondió OK (no 500), ya no probar otros
            if ($result['http_code'] === 200) {
                break;
            }
        }

        $this->newLine();
        $this->info('═══ Test finalizado ═══');
        return self::SUCCESS;
    }

    /**
     * Envía la petición SOAP via cURL
     */
    private function sendSoapRequest(string $endpoint, string $xml, bool $showRaw): ?array
    {
        try {
            $ch = curl_init($endpoint);
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
                    'SOAPAction: "' . self::SOAP_ACTION . '"',
                    'Content-Length: ' . strlen($xml),
                ],
                CURLOPT_HEADER => true, // Para obtener headers de respuesta
            ]);

            $fullResponse = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $connectTime = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
            curl_close($ch);

            if ($error) {
                $this->error("  Error cURL [{$errno}]: {$error}");
                $this->line("  (Tiempo: {$totalTime}s, Conexión: {$connectTime}s)");
                return null;
            }

            $responseHeaders = substr($fullResponse, 0, $headerSize);
            $responseBody = substr($fullResponse, $headerSize);

            // Mostrar resultado
            $httpColor = ($httpCode === 200) ? 'green' : (($httpCode === 500) ? 'red' : 'yellow');
            $this->line("  HTTP Status: <fg={$httpColor}>{$httpCode}</>");
            $this->line("  Tiempo total: <fg=gray>{$totalTime}s</>");
            $this->line("  Tamaño respuesta: <fg=gray>" . strlen($responseBody) . " bytes</>");

            // Mostrar Content-Type del response
            if (preg_match('/Content-Type:\s*(.+)/i', $responseHeaders, $ctMatch)) {
                $this->line("  Content-Type: <fg=gray>" . trim($ctMatch[1]) . "</>");
            }

            if ($showRaw) {
                $this->newLine();
                $this->info('  ── Respuesta XML Cruda ──');
                // Limpiar MIME si es multipart
                $cleanBody = $this->extractXmlFromResponse($responseBody);
                $this->line($cleanBody);
            }

            return [
                'http_code' => $httpCode,
                'body' => $responseBody,
                'headers' => $responseHeaders,
            ];

        } catch (Exception $e) {
            $this->error("  Excepción: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraer XML limpio de una respuesta MTOM/multipart
     */
    private function extractXmlFromResponse(string $body): string
    {
        // Si la respuesta es multipart (MTOM), extraer el XML
        if (str_contains($body, '--uuid:') || str_contains($body, 'Content-Type:')) {
            if (preg_match('/<\?xml.*?\?>.+<\/S:Envelope>/s', $body, $match)) {
                return $match[0];
            }
            // Intentar encontrar el envolvente SOAP
            if (preg_match('/<S:Envelope.*?<\/S:Envelope>/s', $body, $match)) {
                return $match[0];
            }
        }
        return $body;
    }

    /**
     * Analizar y mostrar los campos de la respuesta VUCEM
     */
    private function analyzeResponse(string $responseBody, string $folio, bool $savePdf): void
    {
        $xmlBody = $this->extractXmlFromResponse($responseBody);

        // ── Verificar SOAP Fault ──
        if (preg_match('/<[:\w]*faultstring>(.*?)<\/[:\w]*faultstring>/s', $xmlBody, $faultMatch)) {
            $this->error('  SOAP FAULT: ' . trim($faultMatch[1]));
            if (preg_match('/<[:\w]*faultcode>(.*?)<\/[:\w]*faultcode>/s', $xmlBody, $codeMatch)) {
                $this->line('  Fault Code: <fg=red>' . trim($codeMatch[1]) . '</>');
            }
            return;
        }

        // ── Extraer campos del responseConsultaAcuses ──
        $fields = [
            'code' => null,
            'descripcion' => null,
            'error' => null,
            'mensaje' => null,
            'mensajeErrores' => null,
            'acuseDocumento' => null,
        ];

        // Code
        if (preg_match('/<[:\w]*code>(.*?)<\/[:\w]*code>/s', $xmlBody, $m)) {
            $fields['code'] = trim($m[1]);
            $codeColor = ($fields['code'] === '0') ? 'green' : 'red';
            $this->line("  code: <fg={$codeColor}>{$fields['code']}</>");
        }

        // Error flag
        if (preg_match('/<[:\w]*error>(.*?)<\/[:\w]*error>/s', $xmlBody, $m)) {
            $fields['error'] = strtolower(trim($m[1]));
            $errColor = ($fields['error'] === 'false') ? 'green' : 'red';
            $this->line("  error: <fg={$errColor}>{$fields['error']}</>");
        }

        // Descripcion
        if (preg_match('/<[:\w]*descripcion>(.*?)<\/[:\w]*descripcion>/s', $xmlBody, $m)) {
            $fields['descripcion'] = trim($m[1]);
            $this->line("  descripcion: <fg=cyan>{$fields['descripcion']}</>");
        }

        // Mensaje(s)
        if (preg_match_all('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/s', $xmlBody, $allMsg)) {
            foreach ($allMsg[1] as $idx => $msg) {
                $msgClean = strip_tags(trim($msg));
                if (!empty($msgClean)) {
                    $fields['mensaje'] = $msgClean;
                    $this->line("  mensaje[{$idx}]: <fg=yellow>{$msgClean}</>");
                }
            }
        }

        // MensajeErrores
        if (preg_match_all('/<[:\w]*mensajeErrores>(.*?)<\/[:\w]*mensajeErrores>/s', $xmlBody, $allErr)) {
            foreach ($allErr[1] as $idx => $errMsg) {
                $errClean = strip_tags(trim($errMsg));
                if (!empty($errClean)) {
                    $fields['mensajeErrores'] = $errClean;
                    $this->line("  mensajeErrores[{$idx}]: <fg=red>{$errClean}</>");
                }
            }
        }

        // ── Buscar acuseDocumento (PDF en Base64) ──
        if (preg_match('/<[:\w]*acuseDocumento>(.*?)<\/[:\w]*acuseDocumento>/s', $xmlBody, $m)) {
            $base64Pdf = trim($m[1]);
            // Limpiar
            $base64Pdf = html_entity_decode($base64Pdf, ENT_XML1, 'UTF-8');
            $base64Pdf = preg_replace('/[\r\n\s]+/', '', $base64Pdf);
            
            $fields['acuseDocumento'] = $base64Pdf;
            $pdfSize = strlen(base64_decode($base64Pdf));
            
            $this->newLine();
            $this->info('  ✓ ¡ACUSE PDF ENCONTRADO!');
            $this->line("  Base64 length: <fg=green>" . strlen($base64Pdf) . "</> chars");
            $this->line("  PDF size estimado: <fg=green>" . number_format($pdfSize) . "</> bytes (" . round($pdfSize / 1024, 2) . " KB)");

            if ($savePdf) {
                $pdfPath = storage_path("app/acuse_edocument_{$folio}.pdf");
                $written = file_put_contents($pdfPath, base64_decode($base64Pdf));
                if ($written) {
                    $this->info("  PDF guardado en: {$pdfPath} ({$written} bytes)");
                } else {
                    $this->error("  Error al guardar PDF en: {$pdfPath}");
                }
            }
        } else {
            $this->newLine();
            $this->warn('  ✗ No se encontró acuseDocumento (PDF) en la respuesta.');
        }

        // ── Resumen ──
        $this->newLine();
        $isSuccess = ($fields['code'] === '0' || $fields['code'] === null) 
                  && ($fields['error'] === 'false' || $fields['error'] === null)
                  && !empty($fields['acuseDocumento']);

        if ($isSuccess) {
            $this->info('  ═══ RESULTADO: ÉXITO - Acuse obtenido correctamente ═══');
        } elseif ($fields['error'] === 'false' && empty($fields['acuseDocumento'])) {
            $this->warn('  ═══ RESULTADO: SIN ERROR pero sin PDF (puede que el folio aún esté procesándose) ═══');
        } else {
            $this->error('  ═══ RESULTADO: ERROR en la consulta ═══');
        }
    }
}
