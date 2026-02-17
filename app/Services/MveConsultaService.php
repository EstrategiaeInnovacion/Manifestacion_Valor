<?php

namespace App\Services;

use App\Models\MvAcuse;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para consultar Manifestación de Valor en VUCEM
 *
 * Endpoint: https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService
 * Namespace: http://ws.consultamanifestacion.manifestacion.www.ventanillaunica.gob.mx
 */
class MveConsultaService
{
    // Namespace del servicio VUCEM para Consulta de Manifestación
    private const NS_CONSULTA = 'http://ws.consultamanifestacion.manifestacion.www.ventanillaunica.gob.mx';
    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const NS_WSU = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    private const PASSWORD_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';

    /**
     * Construir XML SOAP para consultar manifestación
     *
     * @param string $folio - Número de operación o Número de MV
     * @param string $rfc - RFC del importador
     * @param string $claveWebService - Clave del web service VUCEM
     * @return array
     */
    public function buildConsultaSoapXml(
        string $folio,
        string $rfc,
        string $claveWebService
    ): array {
        try {
            $rfc = strtoupper(trim($rfc));
            $folio = trim($folio);

            if (empty($folio)) {
                return [
                    'success' => false,
                    'message' => 'El folio es obligatorio para consultar',
                    'xml' => ''
                ];
            }

            if (empty($rfc) || empty($claveWebService)) {
                return [
                    'success' => false,
                    'message' => 'RFC y clave de web service son obligatorios',
                    'xml' => ''
                ];
            }

            // Determinar si es número de MV (formato MNVA...) o número de operación
            $esNumeroMv = preg_match('/^MNVA/i', $folio);

            // Generar timestamp para WS-Security
            $created = gmdate('Y-m-d\TH:i:s\Z');
            $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

            // Construir el nodo de datos según el tipo de folio
            $datosManifestacionXml = $esNumeroMv
                ? '<eDocument>' . htmlspecialchars($folio, ENT_XML1) . '</eDocument>'
                : '<numeroOperacion>' . htmlspecialchars($folio, ENT_XML1) . '</numeroOperacion>';

            Log::info('[MV_CONSULTA] Tipo de consulta', [
                'folio' => $folio,
                'tipo' => $esNumeroMv ? 'Número de MV (eDocument)' : 'Número de Operación',
                'nodo_xml' => $datosManifestacionXml
            ]);

            // Construir XML SOAP completo
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:ws="' . self::NS_CONSULTA . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="' . self::NS_WSSE . '" xmlns:wsu="' . self::NS_WSU . '">
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . $rfc . '</wsse:Username>
            <wsse:Password Type="' . self::PASSWORD_TYPE . '">' . htmlspecialchars($claveWebService, ENT_XML1) . '</wsse:Password>
         </wsse:UsernameToken>
         <wsu:Timestamp wsu:Id="Timestamp-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <ws:consultaManifestacion>
         <datosManifestacion>
            ' . $datosManifestacionXml . '
         </datosManifestacion>
      </ws:consultaManifestacion>
   </soapenv:Body>
</soapenv:Envelope>';

            Log::info('[MV_CONSULTA] XML SOAP generado', [
                'rfc' => $rfc,
                'folio' => $folio,
                'xml_length' => strlen($xml)
            ]);

            return [
                'success' => true,
                'xml' => $xml,
                'xml_formatted' => $this->formatXml($xml)
            ];

        } catch (Exception $e) {
            Log::error('[MV_CONSULTA] Error generando XML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al generar XML: ' . $e->getMessage(),
                'xml' => ''
            ];
        }
    }

    /**
     * Consultar manifestación en VUCEM
     *
     * @param string $numeroOperacion
     * @param string $rfc
     * @param string $claveWebService
     * @return array
     */
    public function consultarManifestacion(
        string $numeroOperacion,
        string $rfc,
        string $claveWebService
    ): array {

        // 1. Construir XML SOAP
        $xmlResult = $this->buildConsultaSoapXml($numeroOperacion, $rfc, $claveWebService);

        if (!$xmlResult['success']) {
            return $xmlResult;
        }

        $xml = $xmlResult['xml'];
        $endpoint = config('vucem.mv_consulta_wsdl', 'https://privados.ventanillaunica.gob.mx/ConsultaManifestacionImpl/ConsultaManifestacionService?wsdl');

        Log::info('[MV_CONSULTA] Iniciando consulta a VUCEM', [
            'rfc' => $rfc,
            'numero_operacion' => $numeroOperacion,
            'endpoint' => $endpoint
        ]);

        try {
            // 2. Enviar petición SOAP usando cURL (mismo método que el registro)
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_TIMEOUT => config('vucem.soap_timeout', 60),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ""',
                    'Content-Length: ' . strlen($xml)
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[MV_CONSULTA] Error cURL', ['error' => $error]);
                return [
                    'success' => false,
                    'message' => 'Error de conexión cURL: ' . $error,
                    'xml_sent' => $xml,
                    'response' => null
                ];
            }

            Log::info('[MV_CONSULTA] Respuesta recibida de VUCEM', [
                'status' => $httpCode,
                'body_length' => strlen($responseBody),
                'response_preview' => substr($responseBody, 0, 500)
            ]);

            // --- DEBUG: VER RESPUESTA COMPLETA DE VUCEM ---
            Log::info('[MV_CONSULTA] RESPUESTA COMPLETA VUCEM: ' . $responseBody);
            // ----------------------------------------------

            // 3. Parsear respuesta
            return $this->parseConsultaResponse($responseBody, $xml, $numeroOperacion);

        } catch (Exception $e) {
            Log::error('[MV_CONSULTA] Error al consultar VUCEM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con VUCEM: ' . $e->getMessage(),
                'xml_sent' => $xml,
                'response' => null
            ];
        }
    }

    /**
     * Parsear respuesta de consulta VUCEM
     * CORREGIDO: Acepta la respuesta si trae Número de MV, aunque falten detalles.
     *
     * @param string $responseBody
     * @param string $xmlSent
     * @param string $numeroOperacion
     * @return array
     */
    private function parseConsultaResponse(string $responseBody, string $xmlSent, string $numeroOperacion): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'xml_sent' => $xmlSent,
            'response' => $responseBody,
            'numero_operacion' => $numeroOperacion,
            'numero_mv' => null,
            'status' => null,
            'fecha_registro' => null,
            'acuse_pdf' => null,
            'errores' => [],
            'datos_manifestacion' => null
        ];

        try {
            // Intentar parsear XML
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);
            libxml_clear_errors();

            // Buscar Número de MV (puede estar como <eDocument> o <numeroManifestacion>)
            if (preg_match('/<[:\w]*eDocument>(.*?)<\/[:\w]*eDocument>/', $responseBody, $matches)) {
                $result['numero_mv'] = trim($matches[1]);
                Log::info('[MV_CONSULTA] Número de MV encontrado (eDocument)', ['numero_mv' => $result['numero_mv']]);
            } elseif (preg_match('/<[:\w]*numeroManifestacion>(.*?)<\/[:\w]*numeroManifestacion>/', $responseBody, $matches)) {
                $result['numero_mv'] = trim($matches[1]);
                Log::info('[MV_CONSULTA] Número de MV encontrado (numeroManifestacion)', ['numero_mv' => $result['numero_mv']]);
            }

            // Buscar status de la manifestación
            if (preg_match('/<[:\w]*estatus>(.*?)<\/[:\w]*estatus>/i', $responseBody, $matches)) {
                $result['status'] = trim($matches[1]);
                Log::info('[MV_CONSULTA] Estado encontrado', ['status' => $result['status']]);
            }

            // Buscar fecha de registro
            if (preg_match('/<[:\w]*fechaRegistro>(.*?)<\/[:\w]*fechaRegistro>/', $responseBody, $matches)) {
                $result['fecha_registro'] = trim($matches[1]);
            }

            // Buscar acuse PDF (base64)
            if (preg_match('/<[:\w]*acusePDF>(.*?)<\/[:\w]*acusePDF>/s', $responseBody, $matches)) {
                $result['acuse_pdf'] = trim($matches[1]);
                Log::info('[MV_CONSULTA] Acuse PDF encontrado', ['size' => strlen($result['acuse_pdf'])]);
            }

            // Extraer datos completos de la manifestación para vista previa
            if (preg_match('/<[:\w]*datosManifestacionValor>(.*?)<\/[:\w]*datosManifestacionValor>/s', $responseBody, $matches)) {
                $result['datos_manifestacion'] = $this->extraerDatosManifestacion($matches[1]);
            }

            // Buscar errores explícitos
            if (preg_match_all('/<[:\w]*mensaje>.*?<[:\w]*codigo>(.*?)<\/[:\w]*codigo>.*?<[:\w]*descripcion>(.*?)<\/[:\w]*descripcion>.*?<\/[:\w]*mensaje>/s', $responseBody, $errorMatches, PREG_SET_ORDER)) {
                foreach ($errorMatches as $error) {
                    $result['errores'][] = [
                        'codigo' => trim($error[1]),
                        'descripcion' => trim($error[2])
                    ];
                }
            }

            // --- LÓGICA DE ÉXITO CORREGIDA ---
            
            // 1. Si hay errores explícitos, fallar.
            if (!empty($result['errores'])) {
                $result['success'] = false;
                $result['message'] = 'Error de VUCEM: ' . $result['errores'][0]['descripcion'];
                Log::warning('[MV_CONSULTA] Error de VUCEM', ['errores' => $result['errores']]);
                return $result;
            }

            // 2. CRÍTICO: Si tenemos el Número de MV (MNVA...), es un ÉXITO,
            // aunque no tengamos todavía el resto de los datos.
            // Esto permite guardar el folio real y luego intentar descargar el PDF.
            if (!empty($result['numero_mv'])) {
                $result['success'] = true;
                $result['message'] = 'Consulta exitosa. Manifestación encontrada: ' . $result['numero_mv'];
                
                if (!empty($result['status'])) {
                    $result['message'] .= ' - Estado: ' . $result['status'];
                }

                Log::info('[MV_CONSULTA] Consulta exitosa (Folio recuperado)', [
                    'numero_mv' => $result['numero_mv'],
                    'status' => $result['status']
                ]);
                
                return $result;
            }

            // 3. Si solo tenemos status pero no folio (caso raro, "En proceso" sin folio asignado)
            if (!empty($result['status'])) {
                // Consideramos éxito parcial para informar al usuario
                $result['success'] = true; 
                $result['message'] = 'Trámite encontrado con estado: ' . $result['status'] . '. (El folio MVE aún no ha sido asignado).';
                return $result;
            }

            // 4. Si llegamos aquí, no encontramos nada útil
            $result['success'] = false;
            $result['message'] = 'No se encontró información para el folio "' . $numeroOperacion . '". Es posible que VUCEM aún esté procesando la solicitud. Intente nuevamente en unos minutos.';
            
            Log::warning('[MV_CONSULTA] Respuesta sin datos identificables', [
                'folio' => $numeroOperacion
            ]);

        } catch (Exception $e) {
            $result['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
            Log::error('[MV_CONSULTA] Error parseando respuesta', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Actualizar acuse con datos de consulta
     *
     * @param MvAcuse $acuse
     * @param array $consultaData
     * @return bool
     */
    public function actualizarAcuseConConsulta(MvAcuse $acuse, array $consultaData): bool
    {
        try {
            $datosActualizar = [];

            // Actualizar número de MV si se obtuvo
            if (!empty($consultaData['numero_mv'])) {
                $datosActualizar['numero_cove'] = $consultaData['numero_mv'];
            }

            // Actualizar acuse PDF si se obtuvo
            if (!empty($consultaData['acuse_pdf'])) {
                $datosActualizar['acuse_pdf'] = $consultaData['acuse_pdf'];
            }

            // Actualizar XML de respuesta
            if (!empty($consultaData['response'])) {
                $datosActualizar['xml_respuesta'] = $consultaData['response'];
            }

            // Actualizar fecha de respuesta
            $datosActualizar['fecha_respuesta'] = now();

            if (!empty($datosActualizar)) {
                $acuse->update($datosActualizar);

                Log::info('[MV_CONSULTA] Acuse actualizado', [
                    'acuse_id' => $acuse->id,
                    'numero_mv' => $consultaData['numero_mv'] ?? null
                ]);

                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('[MV_CONSULTA] Error actualizando acuse', [
                'error' => $e->getMessage(),
                'acuse_id' => $acuse->id
            ]);

            return false;
        }
    }

    /**
     * Extraer datos de manifestación para vista previa - COMPLETO
     */
    private function extraerDatosManifestacion(string $xmlDatos): array
    {
        $datos = [];

        try {
            // ===== Persona Consulta (múltiples) =====
            $datos['persona_consulta'] = [];
            if (preg_match_all('/<personaConsulta>(.*?)<\/personaConsulta>/s', $xmlDatos, $pcMatches, PREG_SET_ORDER)) {
                foreach ($pcMatches as $pcMatch) {
                    $pcXml = $pcMatch[1];
                    $persona = [];
                    if (preg_match('/<rfc>(.*?)<\/rfc>/', $pcXml, $rfc)) {
                        $persona['rfc'] = trim($rfc[1]);
                    }
                    if (preg_match('/<tipoFigura>(.*?)<\/tipoFigura>/', $pcXml, $fig)) {
                        $persona['tipo_figura'] = trim($fig[1]);
                    }
                    if (!empty($persona)) {
                        $datos['persona_consulta'][] = $persona;
                    }
                }
            }

            // ===== Documentos =====
            $datos['documentos'] = [];
            if (preg_match_all('/<documentos>.*?<eDocument>(.*?)<\/eDocument>.*?<\/documentos>/s', $xmlDatos, $docMatches, PREG_SET_ORDER)) {
                foreach ($docMatches as $doc) {
                    $datos['documentos'][] = trim($doc[1]);
                }
                // Guardar el primer documento como referencia directa para obtener el acuse
                if (!empty($datos['documentos'][0])) {
                    $datos['documento_edocument'] = $datos['documentos'][0];
                }
            }

            // ===== Información COVE (múltiples) =====
            $datos['informacion_coves'] = [];
            if (preg_match_all('/<informacionCove>(.*?)<\/informacionCove>/s', $xmlDatos, $coveMatches, PREG_SET_ORDER)) {
                foreach ($coveMatches as $coveMatch) {
                    $coveXml = $coveMatch[1];
                    $coveData = [];

                    // COVE
                    if (preg_match('/<cove>(.*?)<\/cove>/', $coveXml, $m)) {
                        $coveData['cove'] = trim($m[1]);
                    }

                    // Incoterm
                    if (preg_match('/<incoterm>(.*?)<\/incoterm>/', $coveXml, $m)) {
                        $coveData['incoterm'] = trim($m[1]);
                    }

                    // Vinculación
                    if (preg_match('/<existeVinculacion>(.*?)<\/existeVinculacion>/', $coveXml, $m)) {
                        $coveData['existe_vinculacion'] = trim($m[1]) === '1' ? 'Sí' : 'No';
                    }

                    // Pedimento
                    if (preg_match('/<pedimento>(\d+)<\/pedimento>/', $coveXml, $m)) {
                        $coveData['pedimento_numero'] = trim($m[1]);
                    }
                    if (preg_match('/<patente>(.*?)<\/patente>/', $coveXml, $m)) {
                        $coveData['patente'] = trim($m[1]);
                    }
                    if (preg_match('/<aduana>(.*?)<\/aduana>/', $coveXml, $m)) {
                        $coveData['aduana'] = trim($m[1]);
                    }

                    // Método de valoración
                    if (preg_match('/<metodoValoracion>(.*?)<\/metodoValoracion>/', $coveXml, $m)) {
                        $coveData['metodo_valoracion'] = trim($m[1]);
                    }

                    // Precio Pagado
                    if (preg_match('/<precioPagado>(.*?)<\/precioPagado>/s', $coveXml, $m)) {
                        $ppXml = $m[1];
                        $coveData['precio_pagado'] = [];

                        if (preg_match('/<fechaPago>(.*?)<\/fechaPago>/', $ppXml, $fecha)) {
                            $coveData['precio_pagado']['fecha_pago'] = trim($fecha[1]);
                        }
                        if (preg_match('/<total>(.*?)<\/total>/', $ppXml, $total)) {
                            $coveData['precio_pagado']['total'] = trim($total[1]);
                        }
                        if (preg_match('/<tipoMoneda>(.*?)<\/tipoMoneda>/', $ppXml, $mon)) {
                            $coveData['precio_pagado']['moneda'] = trim($mon[1]);
                        }
                        if (preg_match('/<tipoCambio>(.*?)<\/tipoCambio>/', $ppXml, $tc)) {
                            $coveData['precio_pagado']['tipo_cambio'] = trim($tc[1]);
                        }
                        if (preg_match('/<tipoPago>(.*?)<\/tipoPago>/', $ppXml, $tp)) {
                            $coveData['precio_pagado']['tipo_pago'] = trim($tp[1]);
                        }
                        if (preg_match('/<especifique>(.*?)<\/especifique>/', $ppXml, $esp)) {
                            $coveData['precio_pagado']['especifique'] = trim($esp[1]);
                        }
                    }

                    $datos['informacion_coves'][] = $coveData;
                }
            }

            // Compatibilidad: mantener campos simples del primer COVE
            if (!empty($datos['informacion_coves'])) {
                $primerCove = $datos['informacion_coves'][0];
                $datos['cove'] = $primerCove['cove'] ?? null;
                $datos['pedimento_numero'] = $primerCove['pedimento_numero'] ?? null;
                $datos['patente'] = $primerCove['patente'] ?? null;
                $datos['aduana'] = $primerCove['aduana'] ?? null;
                $datos['incoterm'] = $primerCove['incoterm'] ?? null;
                $datos['existe_vinculacion'] = $primerCove['existe_vinculacion'] ?? null;
                $datos['metodo_valoracion'] = $primerCove['metodo_valoracion'] ?? null;
                $datos['precio_pagado'] = $primerCove['precio_pagado'] ?? null;
            }

            // ===== Precios Por Pagar (múltiples) =====
            $datos['precios_por_pagar'] = [];
            if (preg_match_all('/<precioPorPagar>(.*?)<\/precioPorPagar>/s', $xmlDatos, $pppMatches, PREG_SET_ORDER)) {
                foreach ($pppMatches as $ppp) {
                    $pppData = [];
                    if (preg_match('/<fechaPago>(.*?)<\/fechaPago>/', $ppp[1], $fecha)) {
                        $pppData['fecha_pago'] = trim($fecha[1]);
                    }
                    if (preg_match('/<total>(.*?)<\/total>/', $ppp[1], $total)) {
                        $pppData['total'] = trim($total[1]);
                    }
                    if (preg_match('/<tipoMoneda>(.*?)<\/tipoMoneda>/', $ppp[1], $mon)) {
                        $pppData['moneda'] = trim($mon[1]);
                    }
                    if (preg_match('/<tipoCambio>(.*?)<\/tipoCambio>/', $ppp[1], $tc)) {
                        $pppData['tipo_cambio'] = trim($tc[1]);
                    }
                    if (preg_match('/<tipoPago>(.*?)<\/tipoPago>/', $ppp[1], $tp)) {
                        $pppData['tipo_pago'] = trim($tp[1]);
                    }
                    if (preg_match('/<situacionNofechaPago>(.*?)<\/situacionNofechaPago>/', $ppp[1], $sit)) {
                        $pppData['situacion_no_fecha'] = trim($sit[1]);
                    }
                    if (preg_match('/<especifique>(.*?)<\/especifique>/', $ppp[1], $esp)) {
                        $pppData['especifique'] = trim($esp[1]);
                    }
                    $datos['precios_por_pagar'][] = $pppData;
                }
            }

            // ===== Compenso Pago =====
            $datos['compensos_pago'] = [];
            if (preg_match_all('/<compensoPago>(.*?)<\/compensoPago>/s', $xmlDatos, $cpMatches, PREG_SET_ORDER)) {
                foreach ($cpMatches as $cp) {
                    $cpData = [];
                    if (preg_match('/<fecha>(.*?)<\/fecha>/', $cp[1], $fecha)) {
                        $cpData['fecha'] = trim($fecha[1]);
                    }
                    if (preg_match('/<motivo>(.*?)<\/motivo>/', $cp[1], $mot)) {
                        $cpData['motivo'] = trim($mot[1]);
                    }
                    if (preg_match('/<prestacionMercancia>(.*?)<\/prestacionMercancia>/', $cp[1], $pres)) {
                        $cpData['prestacion_mercancia'] = trim($pres[1]);
                    }
                    if (preg_match('/<tipoPago>(.*?)<\/tipoPago>/', $cp[1], $tp)) {
                        $cpData['tipo_pago'] = trim($tp[1]);
                    }
                    if (preg_match('/<especifique>(.*?)<\/especifique>/', $cp[1], $esp)) {
                        $cpData['especifique'] = trim($esp[1]);
                    }
                    $datos['compensos_pago'][] = $cpData;
                }
            }

            // ===== Incrementables (COMPLETO con todos los campos) =====
            $datos['incrementables'] = [];
            if (preg_match_all('/<incrementables>(.*?)<\/incrementables>/s', $xmlDatos, $incrMatches, PREG_SET_ORDER)) {
                foreach ($incrMatches as $incr) {
                    $incrData = [];
                    if (preg_match('/<tipoIncrementable>(.*?)<\/tipoIncrementable>/', $incr[1], $tipo)) {
                        $incrData['tipo'] = trim($tipo[1]);
                    }
                    if (preg_match('/<fechaErogacion>(.*?)<\/fechaErogacion>/', $incr[1], $fecha)) {
                        $incrData['fecha_erogacion'] = trim($fecha[1]);
                    }
                    if (preg_match('/<importe>(.*?)<\/importe>/', $incr[1], $imp)) {
                        $incrData['importe'] = trim($imp[1]);
                    }
                    if (preg_match('/<tipoMoneda>(.*?)<\/tipoMoneda>/', $incr[1], $mon)) {
                        $incrData['moneda'] = trim($mon[1]);
                    }
                    if (preg_match('/<tipoCambio>(.*?)<\/tipoCambio>/', $incr[1], $tc)) {
                        $incrData['tipo_cambio'] = trim($tc[1]);
                    }
                    if (preg_match('/<aCargoImportador>(.*?)<\/aCargoImportador>/', $incr[1], $cargo)) {
                        $incrData['a_cargo_importador'] = trim($cargo[1]) === '1' ? 'Sí' : 'No';
                    }
                    $datos['incrementables'][] = $incrData;
                }
            }

            // ===== Decrementables =====
            $datos['decrementables'] = [];
            if (preg_match_all('/<decrementables>(.*?)<\/decrementables>/s', $xmlDatos, $decrMatches, PREG_SET_ORDER)) {
                foreach ($decrMatches as $decr) {
                    $decrData = [];
                    if (preg_match('/<tipoDecrementable>(.*?)<\/tipoDecrementable>/', $decr[1], $tipo)) {
                        $decrData['tipo'] = trim($tipo[1]);
                    }
                    if (preg_match('/<importe>(.*?)<\/importe>/', $decr[1], $imp)) {
                        $decrData['importe'] = trim($imp[1]);
                    }
                    if (preg_match('/<tipoMoneda>(.*?)<\/tipoMoneda>/', $decr[1], $mon)) {
                        $decrData['moneda'] = trim($mon[1]);
                    }
                    if (preg_match('/<tipoCambio>(.*?)<\/tipoCambio>/', $decr[1], $tc)) {
                        $decrData['tipo_cambio'] = trim($tc[1]);
                    }
                    $datos['decrementables'][] = $decrData;
                }
            }

            // ===== Valor en Aduana =====
            if (preg_match('/<valorEnAduana>(.*?)<\/valorEnAduana>/s', $xmlDatos, $m)) {
                $vaXml = $m[1];
                $datos['valor_aduana'] = [];

                if (preg_match('/<totalPrecioPagado>(.*?)<\/totalPrecioPagado>/', $vaXml, $total)) {
                    $datos['valor_aduana']['precio_pagado'] = trim($total[1]);
                }
                if (preg_match('/<totalPrecioPorPagar>(.*?)<\/totalPrecioPorPagar>/', $vaXml, $total)) {
                    $datos['valor_aduana']['precio_por_pagar'] = trim($total[1]);
                }
                if (preg_match('/<totalIncrementables>(.*?)<\/totalIncrementables>/', $vaXml, $total)) {
                    $datos['valor_aduana']['incrementables'] = trim($total[1]);
                }
                if (preg_match('/<totalDecrementables>(.*?)<\/totalDecrementables>/', $vaXml, $total)) {
                    $datos['valor_aduana']['decrementables'] = trim($total[1]);
                }
                if (preg_match('/<totalValorAduana>(.*?)<\/totalValorAduana>/', $vaXml, $total)) {
                    $datos['valor_aduana']['total'] = trim($total[1]);
                }
            }

        } catch (Exception $e) {
            Log::error('[MV_CONSULTA] Error extrayendo datos de manifestación', [
                'error' => $e->getMessage()
            ]);
        }

        return $datos;
    }

    public function consultarEdocumentAcuse(string $eDocumentFolio, string $rfc, string $claveWebService): array 
    {
        try {
            // ENDPOINT del servicio (SIN ?wsdl - eso es solo para descargar el WSDL)
            $endpoint = 'https://www.ventanillaunica.gob.mx/ventanilla-acuses-HA/ConsultaAcusesServiceWS';

            // SOAP Action indica la operación a invocar
            $soapAction = 'http://www.ventanillaunica.gob.mx/ventanilla/ConsultaAcusesService/consultarAcuseEdocument';

            $rfcClean = strtoupper(trim($rfc));
            $eDocClean = trim($eDocumentFolio);

            // XML Request - Solo consultaAcusesPeticion en el Body (sin wrapper de operación)
            // La operación se especifica en el header SOAPAction, no como elemento XML
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:oxml="http://www.ventanillaunica.gob.mx/consulta/acuses/oxml">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" 
                     xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                     xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsse:UsernameToken>
            <wsse:Username>' . $rfcClean . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($claveWebService, ENT_XML1) . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <oxml:consultaAcusesPeticion>
         <idEdocument>' . htmlspecialchars($eDocClean, ENT_XML1) . '</idEdocument>
      </oxml:consultaAcusesPeticion>
   </soapenv:Body>
</soapenv:Envelope>';

            Log::info('[ACUSE_EDOCUMENT] Iniciando consulta', [
                'idEdocument' => $eDocClean,
                'rfc' => $rfcClean,
                'endpoint' => $endpoint,
                'soapAction' => $soapAction
            ]);

            // Log del XML para debug
            Log::debug('[ACUSE_EDOCUMENT] XML Request', ['xml' => $xml]);

            // Enviar petición SOAP
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false, 
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "' . $soapAction . '"',
                    'Content-Length: ' . strlen($xml)
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[ACUSE_EDOCUMENT] Error cURL', ['error' => $error]);
                return ['success' => false, 'message' => 'Error de conexión: ' . $error, 'acuse_pdf' => null];
            }

            Log::info('[ACUSE_EDOCUMENT] Respuesta recibida', [
                'http_code' => $httpCode,
                'body_length' => strlen($responseBody)
            ]);

            return $this->parseEdocumentAcuseResponse($responseBody, $eDocumentFolio);

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'acuse_pdf' => null];
        }
    }

    /**
     * Consultar acuse de COVE en VUCEM
     * 
     * Usa la operación consultarAcuseCove del servicio ConsultaAcusesServiceWS
     *
     * @param string $coveFolio - Número de COVE (formato COVE...)
     * @param string $rfc - RFC del importador/exportador
     * @param string $claveWebService - Clave del web service VUCEM
     * @return array
     */
    public function consultarCoveAcuse(string $coveFolio, string $rfc, string $claveWebService): array 
    {
        try {
            $endpoint = 'https://www.ventanillaunica.gob.mx/ventanilla-acuses-HA/ConsultaAcusesServiceWS';
            $soapAction = 'http://www.ventanillaunica.gob.mx/ventanilla/ConsultaAcusesService/consultarAcuseCove';

            $rfcClean = strtoupper(trim($rfc));
            $coveClean = trim($coveFolio);

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:oxml="http://www.ventanillaunica.gob.mx/consulta/acuses/oxml">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" 
                     xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                     xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsse:UsernameToken>
            <wsse:Username>' . $rfcClean . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($claveWebService, ENT_XML1) . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <oxml:consultaAcusesPeticion>
         <idEdocument>' . htmlspecialchars($coveClean, ENT_XML1) . '</idEdocument>
      </oxml:consultaAcusesPeticion>
   </soapenv:Body>
</soapenv:Envelope>';

            Log::info('[ACUSE_COVE] Iniciando consulta', [
                'cove' => $coveClean,
                'rfc' => $rfcClean,
                'endpoint' => $endpoint,
                'soapAction' => $soapAction
            ]);

            Log::debug('[ACUSE_COVE] XML Request', ['xml' => $xml]);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false, 
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "' . $soapAction . '"',
                    'Content-Length: ' . strlen($xml)
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[ACUSE_COVE] Error cURL', ['error' => $error]);
                return ['success' => false, 'message' => 'Error de conexión: ' . $error, 'acuse_pdf' => null];
            }

            Log::info('[ACUSE_COVE] Respuesta recibida', [
                'http_code' => $httpCode,
                'body_length' => strlen($responseBody)
            ]);

            return $this->parseEdocumentAcuseResponse($responseBody, $coveFolio);

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'acuse_pdf' => null];
        }
    }

    /**
     * Parsear respuesta de consulta de acuse eDocument (Manifestación de Valor)
     *
     * Response esperado del servicio ConsultaAcusesServiceWS:
     * <responseConsultaAcuses>
     *     <code>0</code>              <!-- 0 = éxito -->
     *     <descripcion>...</descripcion>
     *     <error>false</error>        <!-- false = sin error -->
     *     <mensaje>...</mensaje>
     *     <mensajeErrores>...</mensajeErrores>
     *     <acuseDocumento>BASE64...</acuseDocumento>  <!-- PDF en Base64 -->
     * </responseConsultaAcuses>
     *
     * @param string $responseBody
     * @param string $eDocumentFolio
     * @return array
     */
    private function parseEdocumentAcuseResponse(string $responseBody, string $eDocumentFolio): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'acuse_pdf' => null,
            'code' => null,
            'descripcion' => null
        ];

        try {
            // Log para debug
            Log::debug('[ACUSE_EDOCUMENT] Parseando respuesta', [
                'idEdocument' => $eDocumentFolio,
                'response_length' => strlen($responseBody),
                'response_snippet' => substr($responseBody, 0, 1500)
            ]);

            // Extraer código de respuesta (0 = éxito)
            if (preg_match('/<[:\w]*code>(.*?)<\/[:\w]*code>/s', $responseBody, $matches)) {
                $result['code'] = trim($matches[1]);
            }

            // Extraer descripción
            if (preg_match('/<[:\w]*descripcion>(.*?)<\/[:\w]*descripcion>/s', $responseBody, $matches)) {
                $result['descripcion'] = trim($matches[1]);
            }

            // Verificar flag de error
            $hasError = false;
            if (preg_match('/<[:\w]*error>(.*?)<\/[:\w]*error>/s', $responseBody, $matches)) {
                $errorValue = strtolower(trim($matches[1]));
                $hasError = ($errorValue === 'true' || $errorValue === '1');
            }

            // Extraer mensaje de error si existe
            if (preg_match('/<[:\w]*mensajeErrores>(.*?)<\/[:\w]*mensajeErrores>/s', $responseBody, $matches)) {
                $errorMsg = trim($matches[1]);
                if (!empty($errorMsg)) {
                    $result['message'] = $errorMsg;
                }
            }

            // Extraer mensaje general
            if (preg_match('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/s', $responseBody, $matches)) {
                $mensaje = trim($matches[1]);
                if (empty($result['message'])) {
                    $result['message'] = $mensaje;
                }
            }

            // Si hay error, registrar y retornar
            if ($hasError || ($result['code'] !== null && $result['code'] !== '0')) {
                Log::warning('[ACUSE_EDOCUMENT] Error en respuesta VUCEM', [
                    'idEdocument' => $eDocumentFolio,
                    'code' => $result['code'],
                    'error' => $result['message'],
                    'descripcion' => $result['descripcion']
                ]);
                
                if (empty($result['message'])) {
                    $result['message'] = $result['descripcion'] ?? 'Error desconocido en consulta de acuse';
                }
                return $result;
            }

            // Buscar acuseDocumento (PDF en Base64) - campo correcto del servicio ConsultaAcuses
            if (preg_match('/<[:\w]*acuseDocumento>(.*?)<\/[:\w]*acuseDocumento>/s', $responseBody, $matches)) {
                $base64Pdf = trim($matches[1]);
                
                // Limpiar entidades XML (&#xd; = carriage return, &#xa; = line feed, etc.)
                $base64Pdf = html_entity_decode($base64Pdf, ENT_XML1, 'UTF-8');
                
                // Eliminar saltos de línea y espacios que no son válidos en Base64
                $base64Pdf = preg_replace('/[\r\n\s]+/', '', $base64Pdf);
                
                $result['acuse_pdf'] = $base64Pdf;
                
                Log::info('[ACUSE_EDOCUMENT] Acuse PDF encontrado y limpiado', [
                    'idEdocument' => $eDocumentFolio,
                    'size_base64' => strlen($result['acuse_pdf'])
                ]);
            }

            // Verificar resultado
            if (!empty($result['acuse_pdf'])) {
                $result['success'] = true;
                $result['message'] = 'Acuse de Manifestación de Valor obtenido exitosamente';
            } else {
                $result['message'] = 'No se encontró el acuse PDF para el eDocument: ' . $eDocumentFolio;
                Log::warning('[ACUSE_EDOCUMENT] Acuse PDF no encontrado en respuesta', [
                    'idEdocument' => $eDocumentFolio
                ]);
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
            Log::error('[ACUSE_EDOCUMENT] Error parseando respuesta', [
                'idEdocument' => $eDocumentFolio,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Formatear XML para legibilidad
     */
    private function formatXml(string $xml): string
    {
        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml);
            return $dom->saveXML();
        } catch (Exception $e) {
            return $xml;
        }
    }
}
