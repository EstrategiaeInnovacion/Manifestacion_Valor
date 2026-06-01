<?php

namespace App\Services;

use App\Traits\VucemConnectivityHandler;
use Exception;
use Illuminate\Support\Facades\Log;

class DigitalizarDocumentoService
{
    use VucemConnectivityHandler;

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
        ): array
    {
        // El flujo completo puede tomar varios minutos: conversión PDF + envío VUCEM + polling
        set_time_limit(600);
        ini_set('max_execution_time', '600');

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
            if (!empty($rfcConsulta)) {
                $datosCadena[] = $rfcConsulta;
            }
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

            // Timestamp requerido por VUCEM — ventana dinámica según latencia de red y tamaño del XML
            $latenciaMs = $this->medirLatenciaVucem();
            $xmlEstimadoBytes = strlen($contenidoBase64) + 6000; // base64 + overhead del XML SOAP
            $ventanaSeg = $this->calcularVentanaExpires($xmlEstimadoBytes, $latenciaMs);

            Log::info('[DIGITALIZACION] Red medida para timestamp', [
                'latencia_ms'        => round($latenciaMs, 1),
                'xml_estimado_bytes' => $xmlEstimadoBytes,
                'ventana_expires_seg' => $ventanaSeg,
                'ventana_expires_min' => round($ventanaSeg / 60, 1),
                'clase_red'          => $latenciaMs <= 0 ? 'NO_MEDIDA' : ($latenciaMs > 3000 ? 'MUY_LENTA' : ($latenciaMs > 1000 ? 'LENTA' : ($latenciaMs > 300 ? 'MEDIA' : 'RAPIDA'))),
            ]);

            if ($latenciaMs > 5000) {
                Log::warning('[DIGITALIZACION] Red lenta detectada al servidor VUCEM', ['latencia_ms' => round($latenciaMs, 1)]);
            }

            $created = gmdate("Y-m-d\TH:i:s\Z");
            $expires = gmdate("Y-m-d\TH:i:s\Z", time() + $ventanaSeg);

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
      <dig:registroDigitalizarDocumentoServiceRequest>
         <dig:correoElectronico>' . $emailXml . '</dig:correoElectronico>
         <dig:documento>
            <dig:idTipoDocumento>' . $tipoDocumentoId . '</dig:idTipoDocumento>
            <dig:nombreDocumento>' . $nombreXml . '</dig:nombreDocumento>
            ' . $tagRfcConsulta . '
            <dig:archivo>' . $contenidoBase64 . '</dig:archivo>
         </dig:documento>
         <dig:peticionBase>
            <res:firmaElectronica>
               <res:certificado>' . $firma['certificado'] . '</res:certificado>
               <res:cadenaOriginal>' . $firma['cadenaOriginal'] . '</res:cadenaOriginal>
               <res:firma>' . $firma['firma'] . '</res:firma>
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
            }
            else {
                $xmlSinArchivo = $xml;
            }
            Log::info('[DIGITALIZACION] XML enviado (sin archivo)', [
                'xml_preview' => substr($xmlSinArchivo, 0, 3000),
                'cadena_original' => $firma['cadenaOriginal'],
                'xml_total_length' => strlen($xml),
            ]);

            // Usar cURL directo para mejor control de SSL
            $ch = curl_init($this->endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => trim($xml),
                CURLOPT_TIMEOUT => 300,
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ""',
                    'Content-Length: ' . strlen(trim($xml))
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return $this->handleCurlError($curlError, 'DIGITALIZACION');
            }

            // Detectar si la respuesta es SOAP o MTOM (XOP+XML multipart) aunque el HTTP code sea 5xx.
            // VUCEM puede devolver HTTP 500 con un SOAP Fault válido (ej. "Unknown exception")
            // en formato MTOM — en ese caso NO es caída de infraestructura sino un error puntual de VUCEM.
            $isSoapMtom = str_contains($responseBody, '<S:Body')
                || str_contains($responseBody, '<env:Body')
                || str_contains($responseBody, 'application/xop+xml')
                || str_contains($responseBody, '--uuid:')
                || str_contains($responseBody, 'S:Fault')
                || str_contains($responseBody, 'env:Fault');

            // HTTP 5xx con HTML/texto plano (WebLogic, balanceador, timeout de red) = caída real.
            if (($httpCode >= 500 && !$isSoapMtom)
                || ($httpCode > 0 && !str_contains($responseBody, '<S:Body') && str_contains($responseBody, '<HTML'))) {
                Log::warning('[DIGITALIZACION] VUCEM infraestructura no disponible', [
                    'http_code'    => $httpCode,
                    'body_preview' => substr(strip_tags($responseBody), 0, 200),
                ]);
                $this->registrarErrorVucemPublico($httpCode . ' - Infraestructura VUCEM no disponible', 'DIGITALIZACION');
                return [
                    'success'            => false,
                    'connectivity_error' => true,
                    'message'            => 'El servicio VUCEM no está disponible en este momento (error ' . $httpCode . '). '
                                         . 'Esto es un problema del servicio externo, no del sistema. Intente de nuevo en unos minutos.',
                ];
            }

            // HTTP 5xx con cuerpo SOAP/MTOM = SOAP Fault puntual de VUCEM (no es caída).
            // Extraer el mensaje del fault y devolverlo como error normal (sin connectivity_error).
            if ($httpCode >= 500 && $isSoapMtom) {
                $faultMsg = 'Error interno de VUCEM';
                if (preg_match('/<[:\w]*Text[^>]*>(.*?)<\/[:\w]*Text>/s', $responseBody, $faultM)) {
                    $faultMsg = trim($faultM[1]);
                } elseif (preg_match('/env:Server\s*(.*)/i', strip_tags($responseBody), $faultM)) {
                    $faultMsg = trim($faultM[1]) ?: $faultMsg;
                }
                Log::warning('[DIGITALIZACION] VUCEM devolvió SOAP Fault (HTTP 500)', [
                    'http_code'    => $httpCode,
                    'fault_msg'    => $faultMsg,
                    'body_preview' => substr($responseBody, 0, 500),
                ]);
                return [
                    'success' => false,
                    'message' => 'VUCEM rechazó la solicitud con un error interno: ' . $faultMsg
                               . ' — Espere unos minutos e intente de nuevo.',
                ];
            }

            Log::info('[DIGITALIZACION] Respuesta VUCEM', [
                'status' => $httpCode,
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
                return ['success' => false, 'message' => "VUCEM no aceptó el documento: " . $msg];
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
                // Se pasan las rutas de e.firma porque la consulta también requiere peticionBase con firma
                $eDocumentFolio = $this->pollEDocument(
                    $rfc, $claveWebService, $numOperacion,
                    $certificadoPath, $keyPath, $passwordKey
                );

                if ($eDocumentFolio) {
                    return [
                        'success' => true,
                        'eDocument' => $eDocumentFolio,
                        'numero_operacion' => $numOperacion,
                        'mensaje' => 'Documento digitalizado correctamente. eDocument: ' . $eDocumentFolio,
                    ];
                }

                return [
                    'success' => true,
                    'eDocument' => 'PENDIENTE-Op-' . $numOperacion,
                    'numero_operacion' => $numOperacion,
                    'mensaje' => 'Solicitud aceptada (Op: ' . $numOperacion . '). VUCEM aún está procesando, consulte más tarde.',
                ];
            }

            // VUCEM devolvió SOAP sin eDocument, numeroOperacion ni tieneError reconocible.
            // Respuesta malformada o incompleta — probable problema interno de VUCEM.
            Log::warning('[DIGITALIZACION] Respuesta SOAP sin resultado reconocible', [
                'http_code'    => $httpCode,
                'body_preview' => substr($responseBody, 0, 300),
            ]);
            $this->registrarErrorVucemPublico('Respuesta SOAP sin resultado (HTTP ' . $httpCode . ')', 'DIGITALIZACION');
            return [
                'success'            => false,
                'connectivity_error' => true,
                'message'            => 'VUCEM respondió de forma inesperada. Esto ocurre cuando el servicio tiene problemas temporales. Intente de nuevo en unos minutos.',
            ];

        }
        catch (Exception $e) {
            $msg = $e->getMessage();
            Log::error('[DIGITALIZACION] Excepción: ' . $msg);
            // Si la excepción tiene señales de red/conectividad, usar el canal de diagnóstico
            $esConectividad = str_contains($msg, 'timed out')
                || str_contains($msg, 'SSL')
                || str_contains($msg, 'Connection reset')
                || str_contains(strtolower($msg), 'curl');
            if ($esConectividad) {
                return $this->handleConnectionException($e, 'DIGITALIZACION');
            }
            return ['success' => false, 'message' => 'Error al procesar la solicitud de digitalización. Intente de nuevo.'];
        }
    }

    /**
     * Polling: consulta VUCEM repetidamente hasta obtener el eDocument o agotar intentos.
     */
    private function pollEDocument(
        string $rfc,
        string $claveWebService,
        string $numeroOperacion,
        string $certificadoPath,
        string $keyPath,
        string $passwordKey,
        int $maxIntentos = 5,
        int $intervaloSegundos = 5
        ): ?string
    {
        for ($i = 1; $i <= $maxIntentos; $i++) {
            sleep($intervaloSegundos);
            Log::info("[DIGITALIZACION] Polling intento {$i}/{$maxIntentos}", ['operacion' => $numeroOperacion]);

            $resultado = $this->consultarPorOperacion(
                $rfc, $claveWebService, $numeroOperacion,
                $certificadoPath, $keyPath, $passwordKey
            );

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
     * Consulta el resultado de una operación de digitalización por su número de operación.
     * 
     * Basado en el WSDL real de VUCEM (DigitalizarDocumentoService?wsdl):
     * - Operación: ConsultaEDocumentDigitalizarDocumento
     * - Elemento: consultaDigitalizarDocumentoServiceRequest
     * - Namespace: http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/DigitalizarDocumento
     * - SOAPAction: http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento
     * - Requiere: numeroOperacion + peticionBase(firmaElectronica)
     * - Respuesta: eDocument, numeroDeTramite, cadenaOriginal, respuestaBase
     */
    public function consultarPorOperacion(
        string $rfc,
        string $claveWebService,
        string $numeroOperacion,
        string $certificadoPath = '',
        string $keyPath = '',
        string $passwordKey = ''
        ): ?array
    {
        try {
            // Generar firma electrónica para la consulta
            // Cadena original para consulta: |RFC|numeroOperacion|
            $cadenaOriginal = '|' . trim($rfc) . '|' . trim($numeroOperacion) . '|';

            $firma = null;
            if (!empty($certificadoPath) && !empty($keyPath) && !empty($passwordKey)) {
                $efirmaService = app(EFirmaService::class);
                $firma = $efirmaService->generarFirmaElectronicaConArchivos(
                    $cadenaOriginal, $rfc, $certificadoPath, $keyPath, $passwordKey
                );
            }

            if (!$firma) {
                Log::warning('[DIGITALIZACION] No se pudo generar firma para consulta de operación', [
                    'operacion' => $numeroOperacion,
                    'tiene_cert' => !empty($certificadoPath),
                    'tiene_key' => !empty($keyPath),
                ]);
                return null;
            }

            // Timestamp WS-Security
            $created = gmdate("Y-m-d\TH:i:s\Z");
            $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

            $rfcXml = htmlspecialchars(trim($rfc), ENT_XML1, 'UTF-8');
            $claveXml = htmlspecialchars($claveWebService, ENT_XML1, 'UTF-8');

            // XML SOAP con la estructura correcta según WSDL/XSD de VUCEM
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
         <dig:numeroOperacion>' . $numeroOperacion . '</dig:numeroOperacion>
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

            // Usar cURL directo para mejor control de SSL
            $ch = curl_init($this->endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => trim($xml),
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento"',
                    'Content-Length: ' . strlen(trim($xml))
                ]
            ]);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $this->handleCurlError($curlError, 'DIGITALIZACION_CONSULTA');
                return null;
            }

            Log::info("[DIGITALIZACION] Consulta operación", [
                'operacion' => $numeroOperacion,
                'status' => $httpCode,
                'cadena_original' => $cadenaOriginal,
                'body_preview' => substr($body, 0, 2000),
            ]);

            // SOAP Fault
            if ($httpCode === 500 && str_contains($body, 'Fault')) {
                $faultMsg = '';
                if (preg_match('/<[:\w]*faultstring>(.*?)<\/[:\w]*faultstring>/s', $body, $fMatch)) {
                    $faultMsg = $fMatch[1];
                }
                Log::error('[DIGITALIZACION] SOAP Fault en consulta operación', [
                    'operacion' => $numeroOperacion,
                    'fault' => $faultMsg,
                ]);
                return null;
            }

            // Verificar errores de negocio
            if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/', $body, $matchErr)) {
                if (filter_var($matchErr[1], FILTER_VALIDATE_BOOLEAN)) {
                    $errMsg = '';
                    if (preg_match_all('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/', $body, $allMsgs)) {
                        $errMsg = implode(' | ', $allMsgs[1]);
                    }
                    // "se encuentra procesando" es el estado normal del flujo asíncrono de VUCEM
                    $esEnProceso = str_contains(strtolower($errMsg), 'procesando')
                        || str_contains(strtolower($errMsg), 'procesamiento');
                    if ($esEnProceso) {
                        Log::info('[DIGITALIZACION] Operación aún procesando en VUCEM (estado normal)', [
                            'operacion' => $numeroOperacion,
                            'mensaje' => $errMsg,
                        ]);
                    } else {
                        Log::warning('[DIGITALIZACION] Error de negocio en consulta operación', [
                            'operacion' => $numeroOperacion,
                            'error' => $errMsg,
                        ]);
                    }
                    return null;
                }
            }

            // Buscar eDocument en la respuesta (según XSD: <eDocument>...</eDocument>)
            if (preg_match('/<[:\w]*eDocument>(.*?)<\/[:\w]*eDocument>/', $body, $matchEdoc)) {
                $result = ['eDocument' => $matchEdoc[1]];

                // También extraer numeroDeTramite si existe
                if (preg_match('/<[:\w]*numeroDeTramite>(.*?)<\/[:\w]*numeroDeTramite>/', $body, $matchTramite)) {
                    $result['numeroDeTramite'] = $matchTramite[1];
                }

                Log::info('[DIGITALIZACION] eDocument obtenido de consulta', $result);
                return $result;
            }

            // Si no tiene error pero tampoco eDocument, puede estar aún procesando
            Log::info('[DIGITALIZACION] Consulta sin eDocument (posiblemente aún procesando)', [
                'operacion' => $numeroOperacion,
            ]);
            return null;

        }
        catch (Exception $e) {
            Log::error("[DIGITALIZACION] Error consultando operación: " . $e->getMessage(), [
                'operacion' => $numeroOperacion,
            ]);
            return null;
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
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="' . self::NS_DIG . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
         <wsse:UsernameToken>
            <wsse:Username>' . $rfc . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $claveWebService . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:consultarEdocumentPeticion>
         <dig:numeroEdocument>' . $edocument . '</dig:numeroEdocument>
      </dig:consultarEdocumentPeticion>
   </soapenv:Body>
</soapenv:Envelope>';

            // Usar cURL directo para mejor control de SSL
            $ch = curl_init($this->endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => trim($xml),
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ""',
                    'Content-Length: ' . strlen(trim($xml))
                ]
            ]);

            $body = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return $this->handleCurlError($curlError, 'DIGITALIZACION_EDOCUMENT');
            }

            // Errores de negocio devueltos por VUCEM
            if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/', $body, $matchErr)) {
                if (filter_var($matchErr[1], FILTER_VALIDATE_BOOLEAN)) {
                    $msg = "El eDocument no fue encontrado o no está disponible.";
                    if (preg_match('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/', $body, $mMsg))
                        $msg = $mMsg[1];
                    return ['success' => false, 'message' => $msg];
                }
            }

            // Datos
            $datos = [];
            if (preg_match('/<[:\w]*nombreDocumento>(.*?)<\/[:\w]*nombreDocumento>/', $body, $m))
                $datos['nombre_archivo'] = $m[1];
            if (preg_match('/<[:\w]*rfcFirmante>(.*?)<\/[:\w]*rfcFirmante>/', $body, $m))
                $datos['rfc_firmante'] = $m[1];
            if (preg_match('/<[:\w]*fechaRegistro>(.*?)<\/[:\w]*fechaRegistro>/', $body, $m))
                $datos['fecha_registro'] = $m[1];
            if (preg_match('/<[:\w]*idTipoDocumento>(.*?)<\/[:\w]*idTipoDocumento>/', $body, $m))
                $datos['tipo_documento'] = $m[1];

            if (empty($datos) && !str_contains($body, 'nombreDocumento')) {
                Log::warning('[DIGITALIZACION_EDOCUMENT] Respuesta VUCEM sin datos reconocibles');
                $this->registrarErrorVucemPublico('Respuesta sin datos al consultar eDocument', 'DIGITALIZACION_EDOCUMENT');
                return [
                    'success'            => false,
                    'connectivity_error' => true,
                    'message'            => 'VUCEM no devolvió información. Es posible que el servicio tenga problemas temporales. Intente de nuevo en unos minutos.',
                ];
            }

            return ['success' => true, 'data' => $datos, 'tipo' => 'DIGITALIZACION'];

        }
        catch (Exception $e) {
            $msg = $e->getMessage();
            $esConectividad = str_contains($msg, 'timed out') || str_contains($msg, 'SSL')
                || str_contains($msg, 'Connection reset') || str_contains(strtolower($msg), 'curl');
            if ($esConectividad) {
                return $this->handleConnectionException($e, 'DIGITALIZACION_EDOCUMENT');
            }
            Log::error('[DIGITALIZACION_EDOCUMENT] Excepción: ' . $msg);
            return ['success' => false, 'message' => 'Error al consultar el eDocument. Intente de nuevo.'];
        }
    }

    /**
     * Mide la latencia de red hacia el endpoint de VUCEM haciendo una petición HEAD.
     * Retorna milisegundos (TCP + SSL). Retorna 0.0 si la medición falla.
     */
    private function medirLatenciaVucem(): float
    {
        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
        ]);
        curl_exec($ch);
        $connectTime    = (float) curl_getinfo($ch, CURLINFO_CONNECT_TIME);    // TCP
        $appconnectTime = (float) curl_getinfo($ch, CURLINFO_APPCONNECT_TIME); // TCP + SSL
        curl_close($ch);

        // appconnect incluye TCP, si está disponible es el más completo
        $total = ($appconnectTime > 0) ? $appconnectTime : $connectTime;
        return $total * 1000.0; // convertir a milisegundos
    }

    /**
     * Calcula los segundos de ventana para el WS-Security Timestamp basándose en
     * la latencia medida al servidor VUCEM y el tamaño estimado del XML a enviar.
     *
     * Lógica:
     *   - Latencia alta → conexión lenta → velocidad de subida estimada baja
     *   - Tamaño del XML grande → más tiempo para subir
     *   - Se agrega buffer de 3 min para procesamiento en VUCEM
     *   - Mínimo: 10 min | Máximo: 20 min
     */
    private function calcularVentanaExpires(int $xmlBytes, float $latenciaMs): int
    {
        // Velocidad de subida estimada según clase de latencia (bytes/segundo)
        if ($latenciaMs <= 0) {
            $velocidad = 40 * 1024;  // sin medición: asumir 40 KB/s (conservador)
        } elseif ($latenciaMs < 300) {
            $velocidad = 500 * 1024; // red rápida: ~500 KB/s
        } elseif ($latenciaMs < 1000) {
            $velocidad = 150 * 1024; // red media: ~150 KB/s
        } elseif ($latenciaMs < 3000) {
            $velocidad = 60 * 1024;  // red lenta: ~60 KB/s
        } else {
            $velocidad = 20 * 1024;  // red muy lenta: ~20 KB/s
        }

        $tiempoSubida = (int) ceil($xmlBytes / $velocidad);
        $buffer       = 180; // 3 minutos de buffer para procesamiento VUCEM
        $total        = $tiempoSubida + $buffer;

        // Clamp: entre 10 minutos (600s) y 20 minutos (1200s)
        return max(600, min(1200, $total));
    }
}