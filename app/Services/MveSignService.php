<?php

namespace App\Services;

use App\Models\MvAcuse;
use App\Models\MvClientApplicant;
use App\Models\MvDatosManifestacion;
use App\Models\MvInformacionCove;
use App\Models\MvDocumentos;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MveSignService
{
    private ManifestacionValorService $mveService;
    private EFirmaService $efirmaService;

    public function __construct()
    {
        $this->mveService = new ManifestacionValorService();
        $this->efirmaService = new EFirmaService();
    }

    public function firmarYEnviarManifestacion(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        ?MvDocumentos $documentos,
        string $certificatePath,
        string $privateKeyPath,
        string $privateKeyPassword,
        string $claveWebservice
    ): array {
        try {
            // 1. Cadena Original
            $cadenaOriginal = $this->mveService->buildCadenaOriginal(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos->documentos ?? []
            );

            // --- DEBUG: VER LA CADENA QUE SE VA A FIRMAR ---
            Log::info('MVE - Cadena Original Generada', [
                'longitud' => strlen($cadenaOriginal),
                'CONTENIDO_CADENA' => $cadenaOriginal 
            ]);
            // -----------------------------------------------

            // 2. Firma
            $firmaResult = $this->efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal,
                strtoupper($applicant->applicant_rfc),
                $certificatePath,
                $privateKeyPath,
                $privateKeyPassword
            );

            // 3. Envelope SOAP
            $soapEnvelope = $this->buildSoapEnvelopeFromXsd(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos->documentos ?? [],
                $cadenaOriginal,
                $firmaResult['firma'],
                $firmaResult['certificado'],
                $claveWebservice
            );

            Log::info('MVE - SOAP Envelope construido', [
                'envelope_size' => strlen($soapEnvelope)
            ]);
            
            // --- DEBUG: VER EL XML QUE SE ENVÍA ---
            Log::debug('MVE - XML ENVIADO A VUCEM: ' . $soapEnvelope);
            // --------------------------------------

            // 4. Enviar
            return $this->enviarAVucem($applicant, $datosManifestacion, $soapEnvelope);

        } catch (\Exception $e) {
            Log::error('MVE - Error en firma y envío', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error al firmar la manifestación: ' . $e->getMessage()
            ];
        }
    }

    private function buildSoapEnvelopeFromXsd(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        array $documentos,
        string $cadenaOriginal,
        string $selloDigital,
        string $certificado,
        string $claveWebservice
    ): string {
        $ns = 'http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx';
        $rfcFirmante = strtoupper($applicant->applicant_rfc);
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

        $datosXml = $this->buildDatosManifestacionXml($applicant, $datosManifestacion, $informacionCove, $documentos);

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="' . $ns . '">
    <soapenv:Header>
        <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
            <wsu:Timestamp wsu:Id="TS-1">
                <wsu:Created>' . $timestamp . '</wsu:Created>
                <wsu:Expires>' . $expires . '</wsu:Expires>
            </wsu:Timestamp>
            <wsse:UsernameToken wsu:Id="UsernameToken-1">
                <wsse:Username>' . $rfcFirmante . '</wsse:Username>
                <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $claveWebservice . '</wsse:Password>
            </wsse:UsernameToken>
        </wsse:Security>
    </soapenv:Header>
    <soapenv:Body>
        <ws:registroManifestacion>
            <informacionManifestacion>
                <firmaElectronica>
                    <certificado>' . $certificado . '</certificado>
                    <cadenaOriginal>' . htmlspecialchars($cadenaOriginal, ENT_XML1) . '</cadenaOriginal>
                    <firma>' . $selloDigital . '</firma>
                </firmaElectronica>
                <importador-exportador>
                    <rfc>' . $rfcFirmante . '</rfc>
                </importador-exportador>
                ' . $datosXml . '
            </informacionManifestacion>
        </ws:registroManifestacion>
    </soapenv:Body>
</soapenv:Envelope>';
    }

    private function buildDatosManifestacionXml(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        array $documentos
    ): string {
        $xml = '<datosManifestacionValor>';

        // ... (Personas y Documentos igual que antes) ...
        foreach (($datosManifestacion->persona_consulta ?? []) as $persona) {
            $xml .= '<personaConsulta><rfc>' . strtoupper($persona['rfc'] ?? '') . '</rfc><tipoFigura>' . ($persona['tipo_figura'] ?? '') . '</tipoFigura></personaConsulta>';
        }
        foreach ($documentos as $doc) {
            $folio = $this->mveService->normalizeEdocumentFolio($doc['folio_edocument'] ?? '');
            $xml .= '<documentos><eDocument>' . $folio . '</eDocument></documentos>';
        }

        // FUSIÓN DE DATOS (Mantenemos la lógica que arregló los arrays vacíos)
        $covesList = $informacionCove->informacion_cove ?? [];
        $pedimentosList = $informacionCove->pedimentos ?? [];
        $preciosPagadosList = $informacionCove->precios_pagados ?? $informacionCove->precio_pagado ?? [];
        $incrementablesList = $informacionCove->incrementables ?? [];
        $decrementablesList = $informacionCove->decrementables ?? []; // Agregado por si acaso

        if (!empty($covesList)) {
            if (empty($covesList[0]['pedimentos']) && !empty($pedimentosList)) $covesList[0]['pedimentos'] = $pedimentosList;
            if (empty($covesList[0]['precios_pagados']) && !empty($preciosPagadosList)) $covesList[0]['precios_pagados'] = $preciosPagadosList;
            if (empty($covesList[0]['incrementables']) && !empty($incrementablesList)) $covesList[0]['incrementables'] = $incrementablesList;
            if (empty($covesList[0]['decrementables']) && !empty($decrementablesList)) $covesList[0]['decrementables'] = $decrementablesList;
        }

        foreach ($covesList as $cove) {
            $xml .= '<informacionCove>';
            $xml .= '<cove>' . ($cove['numero_cove'] ?? $cove['cove'] ?? '') . '</cove>';
            $xml .= '<incoterm>' . ($cove['incoterm'] ?? '') . '</incoterm>';
            $vinculacion = ($cove['vinculacion'] ?? $datosManifestacion->existe_vinculacion ?? 0) ? '1' : '0';
            $xml .= '<existeVinculacion>' . $vinculacion . '</existeVinculacion>';

            // Pedimentos
            foreach (($cove['pedimentos'] ?? []) as $ped) {
                $xml .= '<pedimento>';
                $xml .= '<pedimento>' . ($ped['pedimento'] ?? $ped['numero'] ?? '') . '</pedimento>';
                $xml .= '<patente>' . ($ped['patente'] ?? '') . '</patente>';
                $xml .= '<aduana>' . ($ped['aduana'] ?? '') . '</aduana>';
                $xml .= '</pedimento>';
            }

            // Precio Pagado - CRÍTICO: USAR formatXmlDate
            $preciosPagados = $cove['precios_pagados'] ?? $cove['precio_pagado'] ?? [];
            if (empty($preciosPagados) && !empty($informacionCove->precio_pagado)) $preciosPagados = $informacionCove->precio_pagado; // Fallback final

            foreach ($preciosPagados as $pp) {
                $xml .= '<precioPagado>';
                // CAMBIO AQUÍ: formatXmlDate
                $xml .= '<fechaPago>' . $this->mveService->formatXmlDate($pp['fecha'] ?? $pp['fechaPago'] ?? '') . '</fechaPago>';
                $xml .= '<total>' . $this->mveService->formatVucemNumber($pp['importe'] ?? $pp['total'] ?? 0) . '</total>';
                $xml .= '<tipoPago>' . ($pp['tipoPago'] ?? $pp['formaPago'] ?? $pp['tipo_pago'] ?? '') . '</tipoPago>';
                if (!empty($pp['especifique'])) {
                    $xml .= '<especifique>' . htmlspecialchars($pp['especifique'], ENT_XML1) . '</especifique>';
                }
                $xml .= '<tipoMoneda>' . ($pp['tipoMoneda'] ?? $pp['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . $this->mveService->formatVucemNumber($pp['tipoCambio'] ?? $pp['tipo_cambio'] ?? 1) . '</tipoCambio>';
                $xml .= '</precioPagado>';
            }

            // Precio Por Pagar - SOLO incluir si tiene datos reales
            $preciosPorPagar = $cove['precios_por_pagar'] ?? $cove['precio_por_pagar'] ?? [];
            if (!empty($preciosPorPagar)) {
                foreach ($preciosPorPagar as $ppp) {
                    $xml .= '<precioPorPagar>';
                    $xml .= '<fechaPago>' . $this->mveService->formatXmlDate($ppp['fecha'] ?? $ppp['fechaPago'] ?? '') . '</fechaPago>';
                    $xml .= '<total>' . $this->mveService->formatVucemNumber($ppp['importe'] ?? $ppp['total'] ?? 0) . '</total>';
                    if (!empty($ppp['situacionNofechaPago'] ?? $ppp['situacion_no_fecha_pago'] ?? '')) $xml .= '<situacionNofechaPago>' . htmlspecialchars($ppp['situacionNofechaPago'] ?? $ppp['situacion_no_fecha_pago'], ENT_XML1) . '</situacionNofechaPago>';
                    $xml .= '<tipoPago>' . ($ppp['tipoPago'] ?? $ppp['formaPago'] ?? $ppp['tipo_pago'] ?? '') . '</tipoPago>';
                    if (!empty($ppp['especifique'])) $xml .= '<especifique>' . htmlspecialchars($ppp['especifique'], ENT_XML1) . '</especifique>';
                    $xml .= '<tipoMoneda>' . ($ppp['tipoMoneda'] ?? $ppp['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                    $xml .= '<tipoCambio>' . $this->mveService->formatVucemNumber($ppp['tipoCambio'] ?? $ppp['tipo_cambio'] ?? 1) . '</tipoCambio>';
                    $xml .= '</precioPorPagar>';
                }
            }

            // Compensación - SOLO incluir si tiene datos reales
            // ORDEN CORRECTO según XSD: fecha, motivo, prestacionMercancia, tipoPago
            $compensosPago = $cove['compensos_pago'] ?? $cove['compenso_pago'] ?? [];
            if (!empty($compensosPago)) {
                foreach ($compensosPago as $cp) {
                    $xml .= '<compensoPago>';
                    $xml .= '<fecha>' . $this->mveService->formatXmlDate($cp['fecha'] ?? '') . '</fecha>';
                    $xml .= '<motivo>' . htmlspecialchars($cp['motivo'] ?? '', ENT_XML1) . '</motivo>';
                    $xml .= '<prestacionMercancia>' . ($cp['prestacionMercancia'] ?? $cp['prestacion_mercancia'] ?? '') . '</prestacionMercancia>';
                    $xml .= '<tipoPago>' . ($cp['tipoPago'] ?? $cp['formaPago'] ?? $cp['tipo_pago'] ?? '') . '</tipoPago>';
                    if (!empty($cp['especifique'])) $xml .= '<especifique>' . htmlspecialchars($cp['especifique'], ENT_XML1) . '</especifique>';
                    $xml .= '</compensoPago>';
                }
            }

            $xml .= '<metodoValoracion>' . ($cove['metodo_valoracion'] ?? $datosManifestacion->metodo_valoracion ?? '') . '</metodoValoracion>';

            // Incrementables - CRÍTICO: USAR formatXmlDate
            $incrementables = $cove['incrementables'] ?? $cove['incrementable'] ?? [];
            if (empty($incrementables) && !empty($informacionCove->incrementables)) $incrementables = $informacionCove->incrementables;

            foreach ($incrementables as $inc) {
                $xml .= '<incrementables>';
                $xml .= '<tipoIncrementable>' . ($inc['tipoIncrementable'] ?? $inc['incrementable'] ?? $inc['tipo_incrementable'] ?? '') . '</tipoIncrementable>';
                // CAMBIO AQUÍ: formatXmlDate
                $xml .= '<fechaErogacion>' . $this->mveService->formatXmlDate($inc['fechaErogacion'] ?? $inc['fecha_erogacion'] ?? '') . '</fechaErogacion>';
                $xml .= '<importe>' . $this->mveService->formatVucemNumber($inc['importe'] ?? 0) . '</importe>';
                $xml .= '<tipoMoneda>' . ($inc['tipoMoneda'] ?? $inc['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . $this->mveService->formatVucemNumber($inc['tipoCambio'] ?? $inc['tipo_cambio'] ?? 1) . '</tipoCambio>';
                $aCargo = ($inc['aCargoImportador'] ?? $inc['a_cargo_importador'] ?? 0) ? '1' : '0';
                $xml .= '<aCargoImportador>' . $aCargo . '</aCargoImportador>';
                $xml .= '</incrementables>';
            }

            // Decrementables - CRÍTICO: USAR formatXmlDate
            $decrementables = $cove['decrementables'] ?? $cove['decrementable'] ?? [];
            if (empty($decrementables) && !empty($informacionCove->decrementables)) $decrementables = $informacionCove->decrementables;

            foreach ($decrementables as $dec) {
                $xml .= '<decrementables>';
                $xml .= '<tipoDecrementable>' . ($dec['tipoDecrementable'] ?? $dec['decrementable'] ?? $dec['tipo_decrementable'] ?? '') . '</tipoDecrementable>';
                // CAMBIO AQUÍ: formatXmlDate
                $xml .= '<fechaErogacion>' . $this->mveService->formatXmlDate($dec['fechaErogacion'] ?? $dec['fecha_erogacion'] ?? '') . '</fechaErogacion>';
                $xml .= '<importe>' . $this->mveService->formatVucemNumber($dec['importe'] ?? 0) . '</importe>';
                $xml .= '<tipoMoneda>' . ($dec['tipoMoneda'] ?? $dec['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . $this->mveService->formatVucemNumber($dec['tipoCambio'] ?? $dec['tipo_cambio'] ?? 1) . '</tipoCambio>';
                $xml .= '</decrementables>';
            }
            $xml .= '</informacionCove>';
        }

        // Totales
        $valorData = $informacionCove->valor_en_aduana ?? [];
        $xml .= '<valorEnAduana>';
        $xml .= '<totalPrecioPagado>' . $this->mveService->formatVucemNumber($valorData['total_precio_pagado'] ?? 0) . '</totalPrecioPagado>';
        $xml .= '<totalPrecioPorPagar>' . $this->mveService->formatVucemNumber($valorData['total_precio_por_pagar'] ?? 0) . '</totalPrecioPorPagar>';
        $xml .= '<totalIncrementables>' . $this->mveService->formatVucemNumber($valorData['total_incrementables'] ?? 0) . '</totalIncrementables>';
        $xml .= '<totalDecrementables>' . $this->mveService->formatVucemNumber($valorData['total_decrementables'] ?? 0) . '</totalDecrementables>';
        $xml .= '<totalValorAduana>' . $this->mveService->formatVucemNumber($valorData['total_valor_aduana'] ?? 0) . '</totalValorAduana>';
        $xml .= '</valorEnAduana>';

        $xml .= '</datosManifestacionValor>';
        return $xml;
    }

    private function enviarAVucem($applicant, $datosManifestacion, $soapEnvelope): array
    {
        try {
            $endpoint = config('vucem.mv_endpoint');
            $rfcFirmante = strtoupper($applicant->applicant_rfc);

            Log::info('MVE - Enviando a VUCEM via cURL', ['endpoint' => $endpoint]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapEnvelope,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ""',
                    'Content-Length: ' . strlen($soapEnvelope)
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'message' => "Error cURL: $error"];
            }

            Log::info('MVE - Respuesta VUCEM recibida', ['http_code' => $httpCode, 'response_length' => strlen($response)]);
            
            // --- DEBUG: VER RESPUESTA COMPLETA DE VUCEM ---
            Log::info('MVE - RESPUESTA COMPLETA VUCEM: ' . $response);
            // ----------------------------------------------
            
            return $this->procesarRespuestaVucemXml($applicant, $datosManifestacion, $soapEnvelope, $response, $httpCode);
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function procesarRespuestaVucemXml($applicant, $datosManifestacion, $xmlEnviado, $xmlRespuesta, $httpCode): array {
        $numeroOperacion = '';
        $numeroManifestacion = '';
        $acusePdf = null;
        $status = 'DESCONOCIDO';
        $mensaje = '';

        $xmlClean = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $xmlRespuesta);

        if ($httpCode >= 200 && $httpCode < 300) {
            // Capturar número de operación (folio interno de VUCEM para tracking)
            if (preg_match('/<numeroOperacion[^>]*>([^<]+)<\/numeroOperacion>/i', $xmlClean, $m)) {
                $numeroOperacion = trim($m[1]);
                Log::info('[MVE] Número de Operación capturado', ['numero_operacion' => $numeroOperacion]);
            }

            // Capturar Número de Manifestación / eDocument (formato MNVA...) - ESTE ES EL FOLIO REAL
            if (preg_match('/<numeroManifestacion[^>]*>([^<]+)<\/numeroManifestacion>/i', $xmlClean, $m)) {
                $numeroManifestacion = trim($m[1]);
                Log::info('[MVE] Número de Manifestación (eDocument) capturado de respuesta VUCEM', ['numero_manifestacion' => $numeroManifestacion]);
            } elseif (preg_match('/<eDocument[^>]*>([^<]+)<\/eDocument>/i', $xmlClean, $m)) {
                $numeroManifestacion = trim($m[1]);
                Log::info('[MVE] eDocument capturado de respuesta VUCEM', ['eDocument' => $numeroManifestacion]);
            }

            // Capturar acuse PDF en base64 si viene en la respuesta
            if (preg_match('/<acusePDF[^>]*>([^<]+)<\/acusePDF>/is', $xmlClean, $m)) {
                $acusePdf = trim($m[1]);
                Log::info('[MVE] Acuse PDF capturado en respuesta', ['size' => strlen($acusePdf)]);
            }

            // Verificar errores
            if (preg_match('/<descripcionError[^>]*>([^<]+)<\/descripcionError>/i', $xmlClean, $m)) {
                $mensaje = trim($m[1]);
                $status = 'RECHAZADO';
            }
        }

        // CRÍTICO: El folio principal debe ser el numeroManifestacion (eDocument) si existe,
        // ya que es el que se usa para consultas posteriores
        $folioReal = $numeroManifestacion ?: $numeroOperacion;

        if ($folioReal) {
             MvAcuse::create([
                'applicant_id' => $applicant->id,
                'datos_manifestacion_id' => $datosManifestacion->id,
                'folio_manifestacion' => $folioReal,  // Usar numeroManifestacion como folio principal
                'numero_cove' => $numeroManifestacion ?: null,  // eDocument/MNVA...
                'numero_pedimento' => $datosManifestacion->pedimento,
                'acuse_pdf' => $acusePdf,  // Guardar acuse PDF si viene en respuesta
                'xml_enviado' => $xmlEnviado,
                'xml_respuesta' => $xmlRespuesta,
                'status' => 'ENVIADO',
                'fecha_envio' => now(),
                'fecha_respuesta' => now(),
             ]);

             $datosManifestacion->update(['status' => 'enviado']);
             MvInformacionCove::where('applicant_id', $applicant->id)->update(['status' => 'enviado']);

             Log::info('[MVE] Manifestación enviada exitosamente', [
                 'folio_real' => $folioReal,
                 'numero_manifestacion' => $numeroManifestacion,
                 'numero_operacion' => $numeroOperacion,
                 'tiene_acuse_pdf' => !empty($acusePdf)
             ]);

             return [
                 'success' => true,
                 'folio' => $folioReal,
                 'numero_manifestacion' => $numeroManifestacion,
                 'numero_operacion' => $numeroOperacion,
                 'message' => 'Manifestación enviada con éxito. Folio: ' . $folioReal
             ];
        }

        $datosManifestacion->update(['status' => 'rechazado']);
        MvInformacionCove::where('applicant_id', $applicant->id)->update(['status' => 'rechazado']);

        Log::error('[MVE] Error procesando respuesta VUCEM', [
            'mensaje' => $mensaje,
            'http_code' => $httpCode,
            'respuesta_preview' => substr(strip_tags($xmlRespuesta), 0, 500)
        ]);

        // Si no hay mensaje de error explícito, devolvemos todo el XML para verlo en el frontend si es necesario
        return ['success' => false, 'message' => $mensaje ?: 'Error VUCEM (Revisa Logs): ' . strip_tags($xmlRespuesta)];
    }
}