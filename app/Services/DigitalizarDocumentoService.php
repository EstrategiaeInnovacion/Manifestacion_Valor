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
                'doc_id' => $tipoDocumentoId,
                'rfc_length' => strlen($rfc),
                'clave_ws_length' => strlen($claveWebService),
                'clave_ws_preview' => substr($claveWebService, 0, 3) . '***' . substr($claveWebService, -3),
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

            // 5. XML - Escapar valores para XML
            $rfcXml = htmlspecialchars($rfc, ENT_XML1, 'UTF-8');
            $claveXml = htmlspecialchars($claveWebService, ENT_XML1, 'UTF-8');
            $emailXml = htmlspecialchars($email, ENT_XML1, 'UTF-8');
            $nombreXml = htmlspecialchars($nombreArchivoLimpio, ENT_XML1, 'UTF-8');

            $tagRfcConsulta = '';
            if (!empty($rfcConsulta)) {
                $rfcConsultaXml = htmlspecialchars($rfcConsulta, ENT_XML1, 'UTF-8');
                $tagRfcConsulta = "<dig:rfcConsulta>{$rfcConsultaXml}</dig:rfcConsulta>";
            }

            // Timestamp requerido por VUCEM
            $created = gmdate("Y-m-d\TH:i:s\Z");
            $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="'.self::NS_DIG.'" xmlns:res="'.self::NS_RES.'">
   <soapenv:Header>
    <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
        <wsu:Timestamp wsu:Id="TS-1">
            <wsu:Created>'. $created .'</wsu:Created>
            <wsu:Expires>'. $expires .'</wsu:Expires>
        </wsu:Timestamp>
        <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>'. $rfcXml .'</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'. $claveXml .'</wsse:Password>
        </wsse:UsernameToken>
    </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:registroDigitalizarDocumentoServiceRequest>
         <dig:correoElectronico>'. $emailXml .'</dig:correoElectronico>
         <dig:documento>
            <dig:idTipoDocumento>'. $tipoDocumentoId .'</dig:idTipoDocumento>
            <dig:nombreDocumento>'. $nombreXml .'</dig:nombreDocumento>
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
            // Log del XML sin el archivo base64 (para diagnóstico)
            $archivoStart = strpos($xml, '<dig:archivo>');
            $archivoEnd = strpos($xml, '</dig:archivo>');
            if ($archivoStart !== false && $archivoEnd !== false) {
                $xmlSinArchivo = substr($xml, 0, $archivoStart + 13) . '[BASE64_OMITIDO]' . substr($xml, $archivoEnd);
            } else {
                $xmlSinArchivo = $xml;
            }
            Log::info('[DIGITALIZACION] XML enviado (sin archivo)', [
                'xml_preview' => substr($xmlSinArchivo, 0, 3000),
                'cadena_original' => $firma['cadenaOriginal'],
                'xml_total_length' => strlen($xml),
            ]);

            $response = Http::withOptions(['verify' => false, 'timeout' => 300])
                ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => ''])
                ->withBody(trim($xml), 'text/xml')
                ->post($this->endpoint);

            $responseBody = $response->body();
            
            Log::info('[DIGITALIZACION] Respuesta VUCEM', [
                'status' => $response->status(),
                'body_preview' => substr($responseBody, 0, 2000)
            ]);

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
                $numOperacion = $matchOp[1];
                Log::info('[DIGITALIZACION] Recibido numeroOperacion, iniciando polling...', ['operacion' => $numOperacion]);

                // Polling: esperar a que VUCEM procese y devuelva el eDocument
                $eDocumentFolio = $this->pollEDocument($rfc, $claveWebService, $numOperacion);

                if ($eDocumentFolio) {
                    return [
                        'success' => true,
                        'eDocument' => $eDocumentFolio,
                        'mensaje' => 'Documento digitalizado correctamente. eDocument: ' . $eDocumentFolio,
                    ];
                }

                return [
                    'success' => true,
                    'eDocument' => 'PENDIENTE-Op-' . $numOperacion,
                    'mensaje' => 'Solicitud aceptada (Op: ' . $numOperacion . '). VUCEM aún está procesando, consulte más tarde.',
                ];
            }

            return ['success' => false, 'message' => "Respuesta ambigua de VUCEM (Ver Logs)."];

        } catch (Exception $e) {
            Log::error('[DIGITALIZACION] Excepción: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Polling: consulta VUCEM repetidamente hasta obtener el eDocument o agotar intentos.
     */
    private function pollEDocument(string $rfc, string $claveWebService, string $numeroOperacion, int $maxIntentos = 5, int $intervaloSegundos = 5): ?string
    {
        for ($i = 1; $i <= $maxIntentos; $i++) {
            sleep($intervaloSegundos);
            Log::info("[DIGITALIZACION] Polling intento {$i}/{$maxIntentos}", ['operacion' => $numeroOperacion]);

            $resultado = $this->consultarPorOperacion($rfc, $claveWebService, $numeroOperacion);

            if ($resultado && !empty($resultado['eDocument'])) {
                Log::info('[DIGITALIZACION] eDocument obtenido por polling', [
                    'operacion' => $numeroOperacion,
                    'eDocument' => $resultado['eDocument'],
                    'intento' => $i,
                ]);
                return $resultado['eDocument'];
            }
        }

        Log::warning('[DIGITALIZACION] Polling agotado sin obtener eDocument', [
            'operacion' => $numeroOperacion,
            'intentos' => $maxIntentos,
        ]);
        return null;
    }

    /**
     * Consulta el estado de una operación de digitalización por su número de operación.
     * Intenta con múltiples variaciones del elemento XML ya que VUCEM no documenta bien los nombres.
     */
    public function consultarPorOperacion(string $rfc, string $claveWebService, string $numeroOperacion): ?array
    {
        // Variaciones de namespace y elemento a probar
        $intentos = [
            // Intento 1: namespace raíz (como en las respuestas VUCEM)
            [
                'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/',
                'elemento' => 'consultaEDocumentDigitalizarDocumentoServiceRequest',
            ],
            // Intento 2: namespace DigitalizarDocumento con elemento Peticion
            [
                'ns' => self::NS_DIG,
                'elemento' => 'consultarEdocumentDigitalizarDocumentoPeticion',
            ],
        ];

        foreach ($intentos as $i => $intento) {
            try {
                $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="'.$intento['ns'].'">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
         <wsse:UsernameToken>
            <wsse:Username>'.$rfc.'</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.$claveWebService.'</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:'.$intento['elemento'].'>
         <dig:numeroOperacion>'.$numeroOperacion.'</dig:numeroOperacion>
      </dig:'.$intento['elemento'].'>
   </soapenv:Body>
</soapenv:Envelope>';

                $response = Http::withOptions(['verify' => false, 'timeout' => 15])
                    ->withHeaders([
                        'Content-Type' => 'text/xml; charset=utf-8',
                        'SOAPAction' => 'http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento',
                    ])
                    ->withBody(trim($xml), 'text/xml')
                    ->post($this->endpoint);

                $body = $response->body();

                Log::info("[DIGITALIZACION] Consulta operación intento ".($i+1), [
                    'operacion' => $numeroOperacion,
                    'elemento' => $intento['elemento'],
                    'status' => $response->status(),
                    'body_preview' => substr($body, 0, 1500),
                ]);

                // Si hay SOAP Fault (dispatch method not found), probar siguiente variación
                if ($response->status() === 500 && str_contains($body, 'Cannot find dispatch method')) {
                    continue;
                }

                // Verificar errores de negocio
                if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/', $body, $matchErr)) {
                    if (filter_var($matchErr[1], FILTER_VALIDATE_BOOLEAN)) {
                        return null;
                    }
                }

                // Buscar eDocument en la respuesta
                if (preg_match('/<[:\w]*eDocument>(.*?)<\/[:\w]*eDocument>/', $body, $matchEdoc)) {
                    return ['eDocument' => $matchEdoc[1]];
                }
                if (preg_match('/<[:\w]*numeroEdocument>(.*?)<\/[:\w]*numeroEdocument>/', $body, $matchEdoc)) {
                    return ['eDocument' => $matchEdoc[1]];
                }
                if (preg_match('/<[:\w]*numeroEDocument>(.*?)<\/[:\w]*numeroEDocument>/', $body, $matchEdoc)) {
                    return ['eDocument' => $matchEdoc[1]];
                }

                // Si no hubo error de dispatch, no seguir intentando
                return null;

            } catch (Exception $e) {
                Log::error("[DIGITALIZACION] Error consultando operación (intento ".($i+1)."): " . $e->getMessage());
            }
        }

        return null;
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