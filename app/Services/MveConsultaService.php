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
                Log::info('[MV_CONSULTA] Datos de manifestación extraídos para vista previa');
            }

            // Buscar errores
            if (preg_match_all('/<[:\w]*mensaje>.*?<[:\w]*codigo>(.*?)<\/[:\w]*codigo>.*?<[:\w]*descripcion>(.*?)<\/[:\w]*descripcion>.*?<\/[:\w]*mensaje>/s', $responseBody, $errorMatches, PREG_SET_ORDER)) {
                foreach ($errorMatches as $error) {
                    $result['errores'][] = [
                        'codigo' => trim($error[1]),
                        'descripcion' => trim($error[2])
                    ];
                }
            }

            // Determinar éxito - VALIDACIÓN COMPLETA
            // La consulta es exitosa SOLO si:
            // 1. Tiene número de MV
            // 2. Tiene status
            // 3. Tiene datos de manifestación CON información relevante

            $tieneDatosRelevantes = false;
            if (!empty($result['datos_manifestacion'])) {
                $dm = $result['datos_manifestacion'];
                // Verificar que tenga al menos algún campo importante
                $tieneDatosRelevantes = (
                    !empty($dm['cove']) ||
                    !empty($dm['pedimento_numero']) ||
                    !empty($dm['incrementables']) ||
                    !empty($dm['valor_aduana']) ||
                    !empty($dm['precio_pagado'])
                );
            }

            if (!empty($result['numero_mv']) && !empty($result['status']) && $tieneDatosRelevantes) {
                // Consulta exitosa - hay datos reales
                $result['success'] = true;
                $result['message'] = 'Consulta exitosa. Manifestación: ' . $result['numero_mv'] . ' - Estado: ' . $result['status'];

                Log::info('[MV_CONSULTA] Consulta exitosa', [
                    'numero_mv' => $result['numero_mv'],
                    'status' => $result['status'],
                    'tiene_acuse_pdf' => !empty($result['acuse_pdf'])
                ]);
            } elseif (!empty($result['errores'])) {
                // VUCEM devolvió errores explícitos
                $result['success'] = false;
                $result['message'] = 'Error de VUCEM: ' . $result['errores'][0]['descripcion'];

                Log::warning('[MV_CONSULTA] Error de VUCEM', [
                    'errores' => $result['errores']
                ]);
            } elseif (!empty($result['status']) && !$tieneDatosRelevantes) {
                // Tiene status pero NO tiene datos - manifestación no encontrada
                $result['success'] = false;
                $result['message'] = 'No se encontró información para el folio "' . $numeroOperacion . '". Verifique que el folio sea correcto y que corresponda a una manifestación registrada en VUCEM.';

                Log::warning('[MV_CONSULTA] Folio no encontrado o sin datos', [
                    'folio' => $numeroOperacion,
                    'status' => $result['status'],
                    'tiene_datos_manifestacion' => !empty($result['datos_manifestacion'])
                ]);
            } else {
                // No se pudo obtener información
                $result['success'] = false;
                $result['message'] = 'No se pudo obtener información de la manifestación. Verifique el folio ingresado.';

                Log::warning('[MV_CONSULTA] Consulta sin resultados', [
                    'folio' => $numeroOperacion
                ]);
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
            Log::error('[MV_CONSULTA] Error parseando respuesta', [
                'error' => $e->getMessage()
            ]);
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
            // ===== Persona Consulta =====
            if (preg_match('/<personaConsulta>(.*?)<\/personaConsulta>/s', $xmlDatos, $m)) {
                $pcXml = $m[1];
                $datos['persona_consulta'] = [];

                if (preg_match('/<rfc>(.*?)<\/rfc>/', $pcXml, $rfc)) {
                    $datos['persona_consulta']['rfc'] = trim($rfc[1]);
                }
                if (preg_match('/<tipoFigura>(.*?)<\/tipoFigura>/', $pcXml, $fig)) {
                    $datos['persona_consulta']['tipo_figura'] = trim($fig[1]);
                }
            }

            // ===== Documentos =====
            $datos['documentos'] = [];
            if (preg_match_all('/<documentos>.*?<eDocument>(.*?)<\/eDocument>.*?<\/documentos>/s', $xmlDatos, $docMatches, PREG_SET_ORDER)) {
                foreach ($docMatches as $doc) {
                    $datos['documentos'][] = trim($doc[1]);
                }
            }

            // ===== Información COVE =====
            // COVE
            if (preg_match('/<cove>(.*?)<\/cove>/', $xmlDatos, $m)) {
                $datos['cove'] = trim($m[1]);
            }

            // Pedimento
            if (preg_match('/<pedimento>(.*?)<\/pedimento>/', $xmlDatos, $m)) {
                $datos['pedimento_numero'] = trim($m[1]);
            }
            if (preg_match('/<patente>(.*?)<\/patente>/', $xmlDatos, $m)) {
                $datos['patente'] = trim($m[1]);
            }
            if (preg_match('/<aduana>(.*?)<\/aduana>/', $xmlDatos, $m)) {
                $datos['aduana'] = trim($m[1]);
            }

            // Incoterm
            if (preg_match('/<incoterm>(.*?)<\/incoterm>/', $xmlDatos, $m)) {
                $datos['incoterm'] = trim($m[1]);
            }

            // Vinculación
            if (preg_match('/<existeVinculacion>(.*?)<\/existeVinculacion>/', $xmlDatos, $m)) {
                $datos['existe_vinculacion'] = trim($m[1]) === '1' ? 'Sí' : 'No';
            }

            // Método de valoración
            if (preg_match('/<metodoValoracion>(.*?)<\/metodoValoracion>/', $xmlDatos, $m)) {
                $datos['metodo_valoracion'] = trim($m[1]);
            }

            // ===== Precio Pagado =====
            if (preg_match('/<precioPagado>(.*?)<\/precioPagado>/s', $xmlDatos, $m)) {
                $ppXml = $m[1];
                $datos['precio_pagado'] = [];

                if (preg_match('/<fechaPago>(.*?)<\/fechaPago>/', $ppXml, $fecha)) {
                    $datos['precio_pagado']['fecha_pago'] = trim($fecha[1]);
                }
                if (preg_match('/<total>(.*?)<\/total>/', $ppXml, $total)) {
                    $datos['precio_pagado']['total'] = trim($total[1]);
                }
                if (preg_match('/<tipoMoneda>(.*?)<\/tipoMoneda>/', $ppXml, $mon)) {
                    $datos['precio_pagado']['moneda'] = trim($mon[1]);
                }
                if (preg_match('/<tipoCambio>(.*?)<\/tipoCambio>/', $ppXml, $tc)) {
                    $datos['precio_pagado']['tipo_cambio'] = trim($tc[1]);
                }
                if (preg_match('/<tipoPago>(.*?)<\/tipoPago>/', $ppXml, $tp)) {
                    $datos['precio_pagado']['tipo_pago'] = trim($tp[1]);
                }
                if (preg_match('/<especifique>(.*?)<\/especifique>/', $ppXml, $esp)) {
                    $datos['precio_pagado']['especifique'] = trim($esp[1]);
                }
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

    /**
     * Consultar acuse de eDocument en VUCEM
     *
     * @param string $eDocumentFolio - Número de MV (formato MNVA...)
     * @param string $rfc - RFC del importador
     * @param string $claveWebService - Clave del web service VUCEM
     * @return array
     */
    public function consultarEdocumentAcuse(
        string $eDocumentFolio,
        string $rfc,
        string $claveWebService
    ): array {
        try {
            $rfc = strtoupper(trim($rfc));
            $eDocumentFolio = trim($eDocumentFolio);

            if (empty($eDocumentFolio)) {
                return [
                    'success' => false,
                    'message' => 'El folio eDocument es obligatorio',
                    'acuse_pdf' => null
                ];
            }

            // Construir XML SOAP para ConsultarEdocument
            $created = gmdate('Y-m-d\TH:i:s\Z');
            $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://www.ventanillaunica.gob.mx/cove/ws/service/">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . $rfc . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($claveWebService, ENT_XML1) . '</wsse:Password>
         </wsse:UsernameToken>
         <wsu:Timestamp wsu:Id="Timestamp-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <ws:consultarEdocument>
         <eDocument>' . htmlspecialchars($eDocumentFolio, ENT_XML1) . '</eDocument>
      </ws:consultarEdocument>
   </soapenv:Body>
</soapenv:Envelope>';

            $endpoint = 'http://www.ventanillaunica.gob.mx/ventanilla/ConsultarEdocument';

            Log::info('[EDOCUMENT_CONSULTA] Iniciando consulta de acuse eDocument', [
                'rfc' => $rfc,
                'eDocument' => $eDocumentFolio,
                'endpoint' => $endpoint
            ]);

            // Enviar petición SOAP
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
                Log::error('[EDOCUMENT_CONSULTA] Error cURL', ['error' => $error]);
                return [
                    'success' => false,
                    'message' => 'Error de conexión cURL: ' . $error,
                    'acuse_pdf' => null
                ];
            }

            Log::info('[EDOCUMENT_CONSULTA] Respuesta recibida', [
                'status' => $httpCode,
                'body_length' => strlen($responseBody)
            ]);

            // Parsear respuesta para extraer acuse PDF
            return $this->parseEdocumentAcuseResponse($responseBody, $eDocumentFolio);

        } catch (Exception $e) {
            Log::error('[EDOCUMENT_CONSULTA] Error al consultar eDocument acuse', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con VUCEM: ' . $e->getMessage(),
                'acuse_pdf' => null
            ];
        }
    }

    /**
     * Parsear respuesta de consulta de eDocument acuse
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
            'acuse_xml' => null
        ];

        try {
            // Buscar acuse PDF en base64
            if (preg_match('/<[:\w]*acusePDF>(.*?)<\/[:\w]*acusePDF>/s', $responseBody, $matches)) {
                $result['acuse_pdf'] = trim($matches[1]);
                Log::info('[EDOCUMENT_CONSULTA] Acuse PDF encontrado', [
                    'eDocument' => $eDocumentFolio,
                    'size' => strlen($result['acuse_pdf'])
                ]);
            }

            // Buscar acuse XML si está disponible
            if (preg_match('/<[:\w]*acuseXML>(.*?)<\/[:\w]*acuseXML>/s', $responseBody, $matches)) {
                $result['acuse_xml'] = trim($matches[1]);
                Log::info('[EDOCUMENT_CONSULTA] Acuse XML encontrado');
            }

            // Verificar si hubo errores
            if (preg_match('/<[:\w]*descripcionError>(.*?)<\/[:\w]*descripcionError>/i', $responseBody, $matches)) {
                $result['message'] = trim($matches[1]);
                Log::warning('[EDOCUMENT_CONSULTA] Error en respuesta VUCEM', [
                    'error' => $result['message']
                ]);
                return $result;
            }

            if (!empty($result['acuse_pdf'])) {
                $result['success'] = true;
                $result['message'] = 'Acuse de eDocument obtenido exitosamente';
            } else {
                $result['message'] = 'No se encontró el acuse PDF para el eDocument: ' . $eDocumentFolio;
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
            Log::error('[EDOCUMENT_CONSULTA] Error parseando respuesta', [
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
