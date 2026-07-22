<?php

namespace App\Services;

use App\Models\Cove;
use App\Models\MvClientApplicant;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * ConsultarRespuestaCoveService
 *
 * Consulta el e-Document de un COVE enviado a VUCEM usando
 * el servicio ConsultarRespuestaCoveService (solicitar numero_operacion).
 */
class ConsultarRespuestaCoveService
{
    private EFirmaService $efirmaService;

    private const SOAP_ACTION = 'http://www.ventanillaunica.gob.mx/cove/ws/service/ConsultarRespuestaCove';
    private const MAX_INTENTOS = 24; // 24 intentos x 5min = 2 horas máximo

    public function __construct(EFirmaService $efirmaService)
    {
        $this->efirmaService = $efirmaService;
    }

    /**
     * Consulta la respuesta de un COVE enviado a VUCEM.
     * Devuelve el e-Document si ya está disponible, o null si aún está pendiente.
     */
    public function consultarRespuesta(Cove $cove): array
    {
        $applicant = $cove->applicant;

        if (!$applicant) {
            return ['success' => false, 'message' => 'Applicant no encontrado para el COVE.'];
        }

        // Preparar e.firma usando archivos temporales como en CoveService
        $certPath = tempnam(sys_get_temp_dir(), 'cert_');
        $keyPath = tempnam(sys_get_temp_dir(), 'key_');
        
        file_put_contents($certPath, base64_decode($applicant->vucem_cert_file));
        file_put_contents($keyPath, base64_decode($applicant->vucem_key_file));
        $password = $applicant->vucem_password;

        $rfc     = trim($applicant->applicant_rfc);
        $claveWS = trim($applicant->vucem_webservice_key);

        // Construir cadena original para la consulta
        $cadenaOriginal = $this->buildCadenaOriginalConsulta($cove->numero_operacion, $rfc);

        try {
            $firma = $this->efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal,
                $rfc,
                $certPath,
                $keyPath,
                $password
            );
        } catch (Exception $e) {
            @unlink($certPath);
            @unlink($keyPath);
            return ['success' => false, 'message' => 'Error al firmar consulta: ' . $e->getMessage()];
        }

        @unlink($certPath);
        @unlink($keyPath);

        $xml = $this->buildXmlConsulta(
            $cove->numero_operacion,
            $rfc,
            $claveWS,
            $firma['certificado'],
            $cadenaOriginal,
            $firma['firma']
        );

        Log::info('[COVE_CONSULTA] Consultando respuesta en VUCEM', [
            'cove_id'          => $cove->id,
            'numero_operacion' => $cove->numero_operacion,
            'rfc'              => $rfc,
            'xml_sent'         => $xml,
        ]);

        try {
            $endpoint = config('vucem.consultar_respuesta_cove.endpoint', 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarRespuestaCoveService');

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $xml,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "' . self::SOAP_ACTION . '"',
                    'Content-Length: ' . strlen($xml),
                ],
            ]);

            $responseBody = curl_exec($ch);
            $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError    = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::warning('[COVE_CONSULTA] Error cURL', ['error' => $curlError]);
                return ['success' => false, 'pending' => true, 'message' => 'Error de red: ' . $curlError];
            }

            Log::info('[COVE_CONSULTA] Respuesta VUCEM', [
                'cove_id'   => $cove->id,
                'http_code' => $httpCode,
                'body'      => substr($responseBody, 0, 800),
            ]);

            // Guardar respuesta de la consulta en la base de datos para auditoría
            $cove->update(['xml_respuesta' => $responseBody]);

            return $this->parseRespuesta($responseBody, $cove);

        } catch (Exception $e) {
            Log::error('[COVE_CONSULTA] Excepción', ['error' => $e->getMessage()]);
            return ['success' => false, 'pending' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * Construye la cadena original para la consulta de respuesta COVE.
     */
    private function buildCadenaOriginalConsulta(string $numeroOperacion, string $rfc): string
    {
        return '|' . trim($numeroOperacion) . '|' . trim($rfc) . '|';
    }

    /**
     * Construye el sobre SOAP para ConsultarRespuestaCoveServicio.
     */
    private function buildXmlConsulta(
        string $numeroOperacion,
        string $rfc,
        string $claveWS,
        string $certificado,
        string $cadenaOriginal,
        string $firma
    ): string {
        $created = gmdate("Y-m-d\TH:i:s\Z");
        $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:oxml="http://www.ventanillaunica.gob.mx/cove/ws/oxml/">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsu:Timestamp wsu:Id="TS-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . htmlspecialchars($rfc, ENT_XML1) . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($claveWS, ENT_XML1) . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <oxml:solicitarConsultarRespuestaCoveServicio>
         <oxml:numeroOperacion>' . htmlspecialchars($numeroOperacion, ENT_XML1) . '</oxml:numeroOperacion>
         <oxml:firmaElectronica>
            <oxml:certificado>' . $certificado . '</oxml:certificado>
            <oxml:cadenaOriginal>' . htmlspecialchars($cadenaOriginal, ENT_XML1) . '</oxml:cadenaOriginal>
            <oxml:firma>' . $firma . '</oxml:firma>
         </oxml:firmaElectronica>
      </oxml:solicitarConsultarRespuestaCoveServicio>
   </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Parsea la respuesta XML de VUCEM y actualiza el Cove.
     */
    private function parseRespuesta(string $responseBody, Cove $cove): array
    {
        // Limpiar prefijos de namespace para facilitar el parsing
        $clean = preg_replace('/xmlns:?\w*=([\'"])[^\'"]+\1/', '', $responseBody);
        $clean = str_ireplace(['soapenv:', 'S:', 'env:', 'wsse:', 'wsu:', 'oxml:'], '', $clean);

        $xml = @simplexml_load_string($clean);
        if (!$xml) {
            Log::warning('[COVE_CONSULTA] No se pudo parsear XML', ['body' => substr($responseBody, 0, 400)]);
            return ['success' => false, 'pending' => true, 'message' => 'XML de respuesta no parseable.'];
        }

        $body = $xml->Body ?? null;
        if (!$body) {
            return ['success' => false, 'pending' => true, 'message' => 'Sin Body en respuesta VUCEM.'];
        }

        // Buscar Fault (error SOAP)
        if (isset($body->Fault)) {
            $faultString = (string)($body->Fault->faultstring ?? 'Error SOAP desconocido');
            Log::warning('[COVE_CONSULTA] SOAP Fault', ['fault' => $faultString]);
            return ['success' => false, 'pending' => true, 'message' => $faultString];
        }

        // Respuesta de ConsultarRespuestaCove
        $resp = $body->solicitarConsultarRespuestaCoveServicioResponse ?? null;
        if (!$resp) {
            // Intentar también sin prefijo de operación
            foreach ($body->children() as $child) {
                if (str_contains(strtolower($child->getName()), 'consultarrespuesta')) {
                    $resp = $child;
                    break;
                }
            }
        }

        if (!$resp) {
            return ['success' => false, 'pending' => true, 'message' => 'Respuesta VUCEM sin nodo esperado.'];
        }

        // Buscar en respuestasOperaciones
        $edocument    = null;
        $contieneError = false;
        $mensajeError  = null;

        foreach (($resp->respuestasOperaciones ?? []) as $operacion) {
            $error = (string)($operacion->contieneError ?? 'false');
            if ($error === 'true' || $error === '1') {
                $contieneError = true;
                $mensajeError  = (string)($operacion->errores->mensaje ?? 'Error en validación VUCEM.');
                break;
            }
            $eDoc = (string)($operacion->eDocument ?? '');
            if (!empty($eDoc) && $eDoc !== 'PENDIENTE') {
                $edocument = $eDoc;
            }
        }

        if ($contieneError) {
            // Si el error es de firma/cadena original en la consulta, es problema del polling, no del COVE.
            // Lo dejamos en pendiente en lugar de fallar el COVE definitivamente.
            if (str_contains(strtolower($mensajeError), 'cadena original') || str_contains(strtolower($mensajeError), 'firma')) {
                Log::warning('[COVE_CONSULTA] Error de firma en la consulta VUCEM', [
                    'cove_id' => $cove->id,
                    'error'   => $mensajeError,
                ]);
                return ['success' => false, 'pending' => true, 'message' => $mensajeError];
            }

            $cove->update([
                'status'        => 'error',
                'error_mensaje' => $mensajeError,
            ]);
            Log::error('[COVE_CONSULTA] VUCEM reportó error en COVE', [
                'cove_id' => $cove->id,
                'error'   => $mensajeError,
            ]);
            return ['success' => false, 'pending' => false, 'message' => $mensajeError];
        }

        if ($edocument) {
            $cove->update([
                'status'        => 'procesado',
                'edocument'     => $edocument,
                'error_mensaje' => null,
            ]);
            Log::info('[COVE_CONSULTA] ✅ e-Document obtenido exitosamente', [
                'cove_id'   => $cove->id,
                'edocument' => $edocument,
            ]);
            return ['success' => true, 'pending' => false, 'edocument' => $edocument];
        }

        // Aún pendiente
        Log::info('[COVE_CONSULTA] COVE aún pendiente en VUCEM', ['cove_id' => $cove->id]);
        return ['success' => false, 'pending' => true, 'message' => 'VUCEM aún no ha procesado el COVE.'];
    }

    /**
     * Indica si se puede seguir intentando consultar (no se superó el máximo de intentos).
     */
    public static function puedeReintentar(Cove $cove): bool
    {
        return ($cove->intentos_consulta ?? 0) < self::MAX_INTENTOS;
    }
}
