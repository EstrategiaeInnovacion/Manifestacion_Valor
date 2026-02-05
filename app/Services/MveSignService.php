<?php

namespace App\Services;

use App\Models\MvAcuse;
use App\Models\MvClientApplicant;
use App\Models\MvDatosManifestacion;
use App\Models\MvInformacionCove;
use App\Models\MvDocumentos;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use SoapFault;

class MveSignService
{
    private ManifestacionValorService $mveService;
    private EFirmaService $efirmaService;

    public function __construct()
    {
        $this->mveService = new ManifestacionValorService();
        $this->efirmaService = new EFirmaService();
    }

    /**
     * Firmar y enviar Manifestación de Valor a VUCEM
     * Usa EFirmaService con phpcfdi/credentials para procesar la e.firma
     */
    public function firmarYEnviarManifestacion(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        MvDocumentos $documentos,
        string $certificatePath,
        string $privateKeyPath,
        string $privateKeyPassword
    ): array {
        try {
            // 1. Generar la cadena original
            $cadenaOriginal = $this->mveService->buildCadenaOriginal(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos->documentos ?? []
            );

            Log::info('MVE - Cadena Original generada', [
                'applicant_id' => $applicant->id,
                'longitud' => strlen($cadenaOriginal)
            ]);

            // 2. Usar EFirmaService (phpcfdi/credentials) para generar la firma
            // Esto maneja automáticamente archivos .key DER (formato SAT), PEM, contraseñas, etc.
            $firmaResult = $this->efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal,
                strtoupper($applicant->applicant_rfc),
                $certificatePath,
                $privateKeyPath,
                $privateKeyPassword
            );

            Log::info('MVE - Firma generada con phpcfdi/credentials', [
                'applicant_id' => $applicant->id,
                'certificado_longitud' => strlen($firmaResult['certificado']),
                'firma_longitud' => strlen($firmaResult['firma'])
            ]);

            // 3. Construir el envelope SOAP completo según XSD de VUCEM
            $soapEnvelope = $this->buildSoapEnvelopeFromXsd(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos->documentos ?? [],
                $cadenaOriginal,
                $firmaResult['firma'],         // Sello digital (firma Base64)
                $firmaResult['certificado']    // Certificado limpio (sin headers PEM)
            );

            Log::info('MVE - SOAP Envelope construido según XSD', [
                'applicant_id' => $applicant->id,
                'envelope_size' => strlen($soapEnvelope)
            ]);

            // 4. Enviar a VUCEM o guardar en modo prueba
            $enabled = config('vucem.send_manifestation_enabled', false);
            
            if (!$enabled) {
                // Modo prueba: guardar XML sin enviar
                return $this->guardarManifestacionPrueba(
                    $applicant,
                    $datosManifestacion,
                    $soapEnvelope
                );
            }

            // Enviar real a VUCEM
            return $this->enviarAVucem(
                $applicant,
                $datosManifestacion,
                $soapEnvelope
            );

        } catch (\Exception $e) {
            Log::error('MVE - Error en firma y envío', [
                'applicant_id' => $applicant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al firmar la manifestación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Construir envelope SOAP completo según el XSD de VUCEM
     * La estructura es: registroManifestacion > informacionManifestacion > (firmaElectronica, importador-exportador, datosManifestacionValor)
     */
    private function buildSoapEnvelopeFromXsd(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        array $documentos,
        string $cadenaOriginal,
        string $selloDigital,
        string $certificado
    ): string {
        $ns = 'http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx';
        $rfcFirmante = strtoupper($applicant->applicant_rfc);
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

        // Construir datosManifestacionValor
        $datosXml = $this->buildDatosManifestacionXml($applicant, $datosManifestacion, $informacionCove, $documentos);

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:ws="' . $ns . '">
    <soapenv:Header>
        <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" 
            xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
            <wsu:Timestamp wsu:Id="TS-1">
                <wsu:Created>' . $timestamp . '</wsu:Created>
                <wsu:Expires>' . $expires . '</wsu:Expires>
            </wsu:Timestamp>
            <wsse:UsernameToken wsu:Id="UsernameToken-1">
                <wsse:Username>' . $rfcFirmante . '</wsse:Username>
                <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText"></wsse:Password>
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

    /**
     * Construir nodo datosManifestacionValor según XSD
     */
    private function buildDatosManifestacionXml(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        array $documentos
    ): string {
        $xml = '<datosManifestacionValor>';

        // Personas de consulta
        $personasConsulta = $datosManifestacion->persona_consulta ?? [];
        foreach ($personasConsulta as $persona) {
            $xml .= '<personaConsulta>';
            $xml .= '<rfc>' . strtoupper($persona['rfc'] ?? '') . '</rfc>';
            $xml .= '<tipoFigura>' . ($persona['tipo_figura'] ?? '') . '</tipoFigura>';
            $xml .= '</personaConsulta>';
        }

        // Documentos (eDocuments)
        foreach ($documentos as $doc) {
            $folio = $this->mveService->normalizeEdocumentFolio($doc['folio_edocument'] ?? '');
            $xml .= '<documentos>';
            $xml .= '<eDocument>' . $folio . '</eDocument>';
            $xml .= '</documentos>';
        }

        // Información COVE
        $informacionCoveData = $informacionCove->informacion_cove ?? [];
        foreach ($informacionCoveData as $cove) {
            $xml .= '<informacionCove>';
            $xml .= '<cove>' . ($cove['numero_cove'] ?? $cove['cove'] ?? '') . '</cove>';
            $xml .= '<incoterm>' . ($cove['incoterm'] ?? '') . '</incoterm>';
            $xml .= '<existeVinculacion>' . ($cove['vinculacion'] ?? $datosManifestacion->existe_vinculacion ?? 0) . '</existeVinculacion>';

            // Pedimentos
            $pedimentos = $informacionCove->pedimentos ?? [];
            foreach ($pedimentos as $ped) {
                $xml .= '<pedimento>';
                $xml .= '<pedimento>' . ($ped['pedimento'] ?? $ped['numero'] ?? '') . '</pedimento>';
                $xml .= '<patente>' . ($ped['patente'] ?? '') . '</patente>';
                $xml .= '<aduana>' . ($ped['aduana'] ?? '') . '</aduana>';
                $xml .= '</pedimento>';
            }

            // Precio pagado
            $preciosPagados = $cove['precios_pagados'] ?? [];
            foreach ($preciosPagados as $pp) {
                $xml .= '<precioPagado>';
                $xml .= '<fechaPago>' . ($pp['fecha_pago'] ?? date('Y-m-d\TH:i:s')) . '</fechaPago>';
                $xml .= '<total>' . number_format((float)($pp['total'] ?? 0), 2, '.', '') . '</total>';
                $xml .= '<tipoPago>' . ($pp['tipo_pago'] ?? '') . '</tipoPago>';
                if (!empty($pp['especifique'])) {
                    $xml .= '<especifique>' . $pp['especifique'] . '</especifique>';
                }
                $xml .= '<tipoMoneda>' . ($pp['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . number_format((float)($pp['tipo_cambio'] ?? 1), 6, '.', '') . '</tipoCambio>';
                $xml .= '</precioPagado>';
            }

            // Precio por pagar
            $preciosPorPagar = $cove['precios_por_pagar'] ?? [];
            foreach ($preciosPorPagar as $ppp) {
                $xml .= '<precioPorPagar>';
                $xml .= '<fechaPago>' . ($ppp['fecha_pago'] ?? date('Y-m-d\TH:i:s')) . '</fechaPago>';
                $xml .= '<total>' . number_format((float)($ppp['total'] ?? 0), 2, '.', '') . '</total>';
                if (!empty($ppp['situacion_no_fecha_pago'])) {
                    $xml .= '<situacionNofechaPago>' . $ppp['situacion_no_fecha_pago'] . '</situacionNofechaPago>';
                }
                $xml .= '<tipoPago>' . ($ppp['tipo_pago'] ?? '') . '</tipoPago>';
                if (!empty($ppp['especifique'])) {
                    $xml .= '<especifique>' . $ppp['especifique'] . '</especifique>';
                }
                $xml .= '<tipoMoneda>' . ($ppp['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . number_format((float)($ppp['tipo_cambio'] ?? 1), 6, '.', '') . '</tipoCambio>';
                $xml .= '</precioPorPagar>';
            }

            // Compenso pago
            $compensosPago = $cove['compensos_pago'] ?? [];
            foreach ($compensosPago as $cp) {
                $xml .= '<compensoPago>';
                $xml .= '<tipoPago>' . ($cp['tipo_pago'] ?? '') . '</tipoPago>';
                $xml .= '<fecha>' . ($cp['fecha'] ?? date('Y-m-d\TH:i:s')) . '</fecha>';
                $xml .= '<motivo>' . ($cp['motivo'] ?? '') . '</motivo>';
                $xml .= '<prestacionMercancia>' . ($cp['prestacion_mercancia'] ?? '') . '</prestacionMercancia>';
                if (!empty($cp['especifique'])) {
                    $xml .= '<especifique>' . $cp['especifique'] . '</especifique>';
                }
                $xml .= '</compensoPago>';
            }

            $xml .= '<metodoValoracion>' . ($cove['metodo_valoracion'] ?? $datosManifestacion->metodo_valoracion ?? '') . '</metodoValoracion>';

            // Incrementables
            $incrementables = $cove['incrementables'] ?? [];
            foreach ($incrementables as $inc) {
                $xml .= '<incrementables>';
                $xml .= '<tipoIncrementable>' . ($inc['tipo_incrementable'] ?? '') . '</tipoIncrementable>';
                $xml .= '<fechaErogacion>' . ($inc['fecha_erogacion'] ?? date('Y-m-d\TH:i:s')) . '</fechaErogacion>';
                $xml .= '<importe>' . number_format((float)($inc['importe'] ?? 0), 2, '.', '') . '</importe>';
                $xml .= '<tipoMoneda>' . ($inc['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . number_format((float)($inc['tipo_cambio'] ?? 1), 6, '.', '') . '</tipoCambio>';
                $xml .= '<aCargoImportador>' . ($inc['a_cargo_importador'] ?? 0) . '</aCargoImportador>';
                $xml .= '</incrementables>';
            }

            // Decrementables
            $decrementables = $cove['decrementables'] ?? [];
            foreach ($decrementables as $dec) {
                $xml .= '<decrementables>';
                $xml .= '<tipoDecrementable>' . ($dec['tipo_decrementable'] ?? '') . '</tipoDecrementable>';
                $xml .= '<fechaErogacion>' . ($dec['fecha_erogacion'] ?? date('Y-m-d\TH:i:s')) . '</fechaErogacion>';
                $xml .= '<importe>' . number_format((float)($dec['importe'] ?? 0), 2, '.', '') . '</importe>';
                $xml .= '<tipoMoneda>' . ($dec['tipo_moneda'] ?? 'USD') . '</tipoMoneda>';
                $xml .= '<tipoCambio>' . number_format((float)($dec['tipo_cambio'] ?? 1), 6, '.', '') . '</tipoCambio>';
                $xml .= '</decrementables>';
            }

            $xml .= '</informacionCove>';
        }

        // Valor en aduana
        $valorData = $informacionCove->valor_en_aduana ?? [];
        $xml .= '<valorEnAduana>';
        $xml .= '<totalPrecioPagado>' . number_format((float)($valorData['total_precio_pagado'] ?? 0), 2, '.', '') . '</totalPrecioPagado>';
        $xml .= '<totalPrecioPorPagar>' . number_format((float)($valorData['total_precio_por_pagar'] ?? 0), 2, '.', '') . '</totalPrecioPorPagar>';
        $xml .= '<totalIncrementables>' . number_format((float)($valorData['total_incrementables'] ?? 0), 2, '.', '') . '</totalIncrementables>';
        $xml .= '<totalDecrementables>' . number_format((float)($valorData['total_decrementables'] ?? 0), 2, '.', '') . '</totalDecrementables>';
        $xml .= '<totalValorAduana>' . number_format((float)($valorData['total_valor_aduana'] ?? 0), 2, '.', '') . '</totalValorAduana>';
        $xml .= '</valorEnAduana>';

        $xml .= '</datosManifestacionValor>';

        return $xml;
    }

    /**
     * Enviar envelope SOAP a VUCEM usando cURL
     * El envelope ya viene construido según el XSD de VUCEM
     */
    private function enviarAVucem(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        string $soapEnvelope
    ): array {
        try {
            $endpoint = config('vucem.mv_endpoint');
            $rfcFirmante = strtoupper($applicant->applicant_rfc);

            Log::info('MVE - Enviando a VUCEM via cURL', [
                'endpoint' => $endpoint,
                'applicant_id' => $applicant->id,
                'rfc' => $rfcFirmante
            ]);

            // Enviar con cURL - SOAPAction vacío según WSDL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapEnvelope,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,        // 2 minutos timeout total
                CURLOPT_CONNECTTIMEOUT => 60,  // 1 minuto timeout conexión
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: ""',  // Vacío según WSDL: <soap:operation soapAction=""/>
                    'Content-Length: ' . strlen($soapEnvelope)
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('MVE - Error cURL', ['error' => $curlError]);
                return [
                    'success' => false,
                    'message' => 'Error de conexión con VUCEM: ' . $curlError
                ];
            }

            Log::info('MVE - Respuesta VUCEM recibida', [
                'http_code' => $httpCode,
                'response_length' => strlen($response)
            ]);

            // Guardar respuesta para debug
            Log::debug('MVE - XML Respuesta VUCEM', ['xml' => $response]);

            // Procesar respuesta XML
            return $this->procesarRespuestaVucemXml(
                $applicant,
                $datosManifestacion,
                $soapEnvelope,
                $response,
                $httpCode
            );

        } catch (\Exception $e) {
            Log::error('MVE - Error enviando a VUCEM', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con VUCEM: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar respuesta XML de VUCEM
     */
    private function procesarRespuestaVucemXml(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        string $xmlEnviado,
        string $xmlRespuesta,
        int $httpCode
    ): array {
        try {
            // Extraer datos de la respuesta SOAP
            $folio = '';
            $status = 'DESCONOCIDO';
            $mensaje = '';

            // Limpiar namespaces para facilitar parsing
            $xmlClean = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$3', $xmlRespuesta);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                // Intentar parsear respuesta exitosa
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($xmlClean);
                
                if ($xml !== false) {
                    // Buscar elementos de respuesta
                    $body = $xml->Body ?? $xml->children('soap', true)->Body ?? null;
                    
                    if ($body) {
                        // Buscar folio y status en la respuesta
                        $responseText = $body->asXML();
                        
                        if (preg_match('/<folio[^>]*>([^<]+)<\/folio>/i', $responseText, $matches)) {
                            $folio = trim($matches[1]);
                        }
                        if (preg_match('/<folioManifestacion[^>]*>([^<]+)<\/folioManifestacion>/i', $responseText, $matches)) {
                            $folio = trim($matches[1]);
                        }
                        if (preg_match('/<status[^>]*>([^<]+)<\/status>/i', $responseText, $matches)) {
                            $status = trim($matches[1]);
                        }
                        if (preg_match('/<mensaje[^>]*>([^<]+)<\/mensaje>/i', $responseText, $matches)) {
                            $mensaje = trim($matches[1]);
                        }
                        if (preg_match('/<descripcion[^>]*>([^<]+)<\/descripcion>/i', $responseText, $matches)) {
                            $mensaje = $mensaje ?: trim($matches[1]);
                        }
                    }
                }
                
                // Si no encontramos folio pero HTTP fue exitoso, verificar si hay error SOAP
                if (empty($folio) && strpos($xmlRespuesta, 'Fault') !== false) {
                    $status = 'RECHAZADO';
                    if (preg_match('/<faultstring[^>]*>([^<]+)<\/faultstring>/i', $xmlRespuesta, $matches)) {
                        $mensaje = trim($matches[1]);
                    }
                }
            } else {
                // Error HTTP
                $status = 'ERROR';
                $mensaje = "Error HTTP $httpCode al comunicarse con VUCEM";
            }

            // Determinar éxito
            $success = !empty($folio) && $status !== 'RECHAZADO' && $status !== 'ERROR';
            
            if (empty($folio) && $status !== 'RECHAZADO' && $status !== 'ERROR') {
                $status = 'PENDIENTE';
                $mensaje = $mensaje ?: 'Respuesta recibida pero sin folio. Verifique en portal VUCEM.';
            }

            // Si hubo error HTTP o la respuesta indica error, NO crear acuse ni marcar como completada
            if ($status === 'ERROR' || $status === 'RECHAZADO' || $httpCode >= 400) {
                Log::warning('MVE - Error de VUCEM, no se marca como completada', [
                    'applicant_id' => $applicant->id,
                    'http_code' => $httpCode,
                    'status' => $status,
                    'mensaje' => $mensaje
                ]);
                
                return [
                    'success' => false,
                    'message' => $mensaje ?: "Error HTTP $httpCode al comunicarse con VUCEM",
                    'folio' => null,
                    'acuse_id' => null,
                    'status' => $status
                ];
            }

            // Solo crear acuse si la respuesta fue exitosa
            $acuse = MvAcuse::create([
                'applicant_id' => $applicant->id,
                'datos_manifestacion_id' => $datosManifestacion->id,
                'folio_manifestacion' => $folio ?: 'PENDIENTE-' . date('YmdHis'),
                'numero_pedimento' => $datosManifestacion->pedimento,
                'xml_enviado' => $xmlEnviado,
                'xml_respuesta' => $xmlRespuesta,
                'status' => $status,
                'mensaje_vucem' => $mensaje,
                'fecha_envio' => now(),
                'fecha_respuesta' => now(),
            ]);

            // Solo actualizar status si fue exitoso
            $this->actualizarStatusTablas($applicant->id, 'enviado');

            return [
                'success' => $success,
                'message' => $mensaje ?: ($success ? 'Manifestación enviada correctamente' : 'Error al procesar respuesta'),
                'folio' => $folio,
                'acuse_id' => $acuse->id,
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('MVE - Error procesando respuesta VUCEM', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la respuesta de VUCEM: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guardar manifestación en modo prueba (sin enviar a VUCEM)
     */
    private function guardarManifestacionPrueba(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        string $xmlManifestacion
    ): array {
        $folioSimulado = 'MV' . date('YmdHis') . rand(1000, 9999);

        $acuse = MvAcuse::create([
            'applicant_id' => $applicant->id,
            'datos_manifestacion_id' => $datosManifestacion->id,
            'folio_manifestacion' => $folioSimulado,
            'numero_pedimento' => $datosManifestacion->pedimento,
            'xml_enviado' => $xmlManifestacion,
            'xml_respuesta' => '<respuesta>MODO PRUEBA - NO ENVIADO A VUCEM</respuesta>',
            'status' => 'PRUEBA',
            'mensaje_vucem' => 'Manifestación generada en modo prueba. No se envió a VUCEM.',
            'fecha_envio' => now(),
            'fecha_respuesta' => now(),
        ]);

        // Actualizar status de las tablas originales a 'enviado' para que no aparezcan en pendientes
        $this->actualizarStatusTablas($applicant->id, 'enviado');

        return [
            'success' => true,
            'message' => 'Manifestación generada correctamente (MODO PRUEBA)',
            'folio' => $folioSimulado,
            'acuse_id' => $acuse->id,
            'modo' => 'prueba'
        ];
    }

    /**
     * Actualizar el status de todas las tablas relacionadas con la manifestación
     */
    private function actualizarStatusTablas(int $applicantId, string $nuevoStatus): void
    {
        try {
            // Actualizar MvDatosManifestacion
            MvDatosManifestacion::where('applicant_id', $applicantId)
                ->whereIn('status', ['borrador', 'guardado', 'completado'])
                ->update(['status' => $nuevoStatus]);

            // Actualizar MvInformacionCove
            MvInformacionCove::where('applicant_id', $applicantId)
                ->whereIn('status', ['borrador', 'guardado', 'completado'])
                ->update(['status' => $nuevoStatus]);

            // Actualizar MvDocumentos
            MvDocumentos::where('applicant_id', $applicantId)
                ->whereIn('status', ['borrador', 'guardado', 'completado'])
                ->update(['status' => $nuevoStatus]);

            Log::info('MVE - Status actualizado en todas las tablas', [
                'applicant_id' => $applicantId,
                'nuevo_status' => $nuevoStatus
            ]);
        } catch (\Exception $e) {
            Log::error('MVE - Error actualizando status de tablas', [
                'applicant_id' => $applicantId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
