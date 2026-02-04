<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DigitalizarDocumentoService
{
    private string $endpoint = 'https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService';
    
    private const NS_DIG = 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/DigitalizarDocumento';
    private const NS_RES = 'http://www.ventanillaunica.gob.mx/common/ws/oxml/respuesta';

    public function digitalizarDocumento(
        string $rfc,
        string $claveWebService,
        string $tipoDocumentoId,
        string $nombreArchivo,
        string $contenidoBase64,
        string $certificadoPath,
        string $keyPath,
        string $passwordKey,
        string $email,
        string $rfcConsulta = '' 
    ): array {
        try {
            // 1. LIMPIEZA
            $nombreArchivoLimpio = $this->limpiarNombreArchivo($nombreArchivo);
            $contenidoBase64 = str_replace(["\r", "\n"], '', $contenidoBase64);

            Log::info('[DIGITALIZACION] Iniciando...', [
                'rfc' => $rfc,
                'rfc_consulta' => $rfcConsulta,
                'doc_id' => $tipoDocumentoId
            ]);

            // 2. HASH
            $archivoBinario = base64_decode($contenidoBase64);
            $hashArchivo = sha1($archivoBinario);
            
            // 3. CADENA ORIGINAL
            $datosCadena = [$rfc, $email, $tipoDocumentoId, $nombreArchivoLimpio];
            if (!empty($rfcConsulta)) { $datosCadena[] = $rfcConsulta; }
            $datosCadena[] = $hashArchivo;
            $cadenaOriginal = '|' . implode('|', $datosCadena) . '|';

            // 4. FIRMA
            $efirmaService = app(EFirmaService::class);
            $firma = $efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal, $rfc, $certificadoPath, $keyPath, $passwordKey
            );

            // 5. XML
            $tagRfcConsulta = '';
            if (!empty($rfcConsulta)) {
                $tagRfcConsulta = "<dig:rfcConsulta>{$rfcConsulta}</dig:rfcConsulta>";
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="'.self::NS_DIG.'" xmlns:res="'.self::NS_RES.'">
   <soapenv:Header>
    <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
        <wsse:UsernameToken>
            <wsse:Username>'. $rfc .'</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'. $claveWebService .'</wsse:Password>
        </wsse:UsernameToken>
    </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:registroDigitalizarDocumentoServiceRequest>
         <dig:correoElectronico>'. $email .'</dig:correoElectronico>
         <dig:documento>
            <dig:idTipoDocumento>'. $tipoDocumentoId .'</dig:idTipoDocumento>
            <dig:nombreDocumento>'. $nombreArchivoLimpio .'</dig:nombreDocumento>
            '. $tagRfcConsulta .'
            <dig:archivo>'. $contenidoBase64 .'</dig:archivo>
         </dig:documento>
         <dig:peticionBase>
            <res:firmaElectronica>
               <res:certificado>'. $firma['certificado'] .'</res:certificado>
               <res:cadenaOriginal>'. $firma['cadenaOriginal'] .'</res:cadenaOriginal>
               <res:firma>'. $firma['firma'] .'</res:firma>
            </res:firmaElectronica>
         </dig:peticionBase>
      </dig:registroDigitalizarDocumentoServiceRequest>
   </soapenv:Body>
</soapenv:Envelope>';

            // 6. ENVÍO
            $response = Http::withOptions(['verify' => false, 'timeout' => 300])
                ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => ''])
                ->withBody(trim($xml), 'text/xml')
                ->post($this->endpoint);

            $responseBody = $response->body();
            
            // 7. RESPUESTA INTELIGENTE
            $tieneError = false;
            if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/', $responseBody, $matchErr)) {
                $tieneError = filter_var($matchErr[1], FILTER_VALIDATE_BOOLEAN);
            }

            if ($tieneError) {
                $msg = "Error desconocido";
                if (preg_match_all('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/', $responseBody, $allErrors)) {
                    $msg = implode(" | ", $allErrors[1]);
                }
                return ['success' => false, 'message' => "VUCEM Rechazo: " . $msg];
            }
            
            if (preg_match('/<[:\w]*eDocument>(.*?)<\/[:\w]*eDocument>/', $responseBody, $matches)) {
                return [
                    'success' => true,
                    'eDocument' => $matches[1],
                    'mensaje' => 'Documento digitalizado correctamente.',
                ];
            }

            if (preg_match('/<[:\w]*numeroOperacion>(.*?)<\/[:\w]*numeroOperacion>/', $responseBody, $matchOp)) {
                return [
                    'success' => true,
                    'eDocument' => 'PENDIENTE-Op-' . $matchOp[1], 
                    'mensaje' => 'Solicitud aceptada. Procesando Operación: ' . $matchOp[1],
                ];
            }

            return ['success' => false, 'message' => "Respuesta ambigua de VUCEM (Ver Logs)."];

        } catch (Exception $e) {
            Log::error('[DIGITALIZACION] Excepción: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function limpiarNombreArchivo($nombre): string
    {
        $info = pathinfo($nombre);
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $info['filename']);
        return substr($filename, 0, 45) . '.' . ($info['extension'] ?? 'pdf');
    }

    /**
     * Consulta documentos digitalizados escaneados (ID 112, 441, etc.)
     * NUEVO MÉTODO AGREGADO
     */
    public function consultarEdocument(string $rfc, string $claveWebService, string $edocument): array
    {
        try {
            $edocument = trim($edocument);
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="'.self::NS_DIG.'">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
         <wsse:UsernameToken>
            <wsse:Username>'.$rfc.'</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.$claveWebService.'</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:consultarEdocumentPeticion>
         <dig:numeroEdocument>'.$edocument.'</dig:numeroEdocument>
      </dig:consultarEdocumentPeticion>
   </soapenv:Body>
</soapenv:Envelope>';

            $response = Http::withOptions(['verify' => false, 'timeout' => 60])
                ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => ''])
                ->withBody(trim($xml), 'text/xml')
                ->post($this->endpoint);

            $body = $response->body();

            // Errores
            if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/', $body, $matchErr)) {
                if (filter_var($matchErr[1], FILTER_VALIDATE_BOOLEAN)) {
                    $msg = "No encontrado";
                    if (preg_match('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/', $body, $mMsg)) $msg = $mMsg[1];
                    return ['success' => false, 'message' => $msg];
                }
            }

            // Datos
            $datos = [];
            if (preg_match('/<[:\w]*nombreDocumento>(.*?)<\/[:\w]*nombreDocumento>/', $body, $m)) $datos['nombre_archivo'] = $m[1];
            if (preg_match('/<[:\w]*rfcFirmante>(.*?)<\/[:\w]*rfcFirmante>/', $body, $m)) $datos['rfc_firmante'] = $m[1];
            if (preg_match('/<[:\w]*fechaRegistro>(.*?)<\/[:\w]*fechaRegistro>/', $body, $m)) $datos['fecha_registro'] = $m[1];
            if (preg_match('/<[:\w]*idTipoDocumento>(.*?)<\/[:\w]*idTipoDocumento>/', $body, $m)) $datos['tipo_documento'] = $m[1];

            if (empty($datos) && !str_contains($body, 'nombreDocumento')) {
                return ['success' => false, 'message' => 'eDocument no encontrado en Digitalización.'];
            }

            return ['success' => true, 'data' => $datos, 'tipo' => 'DIGITALIZACION'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}