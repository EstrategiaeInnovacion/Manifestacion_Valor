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

            // 3. Construir el XML para enviar a VUCEM
            $xmlManifestacion = $this->buildXmlManifestacion(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos->documentos ?? [],
                $cadenaOriginal,
                $firmaResult['firma'],         // Sello digital (firma Base64)
                $firmaResult['certificado']    // Certificado limpio (sin headers PEM)
            );

            Log::info('MVE - XML construido', [
                'applicant_id' => $applicant->id,
                'xml_size' => strlen($xmlManifestacion)
            ]);

            // 4. Enviar a VUCEM o guardar en modo prueba
            $enabled = config('vucem.send_manifestation_enabled', false);
            
            if (!$enabled) {
                // Modo prueba: guardar XML sin enviar
                return $this->guardarManifestacionPrueba(
                    $applicant,
                    $datosManifestacion,
                    $xmlManifestacion
                );
            }

            // Enviar real a VUCEM
            return $this->enviarAVucem(
                $applicant,
                $datosManifestacion,
                $xmlManifestacion
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
     * Construir XML de manifestación de valor para VUCEM
     */
    private function buildXmlManifestacion(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        MvInformacionCove $informacionCove,
        array $documentos,
        string $cadenaOriginal,
        string $selloDigital,
        string $certificado
    ): string {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><datosManifestacionValor xmlns="http://www.ventanillaunica.gob.mx/IngresoManifestacion/"></datosManifestacionValor>');

        // RFC Importador
        $xml->addChild('rfcImportador', strtoupper($applicant->applicant_rfc));

        // Personas de consulta
        $personasConsulta = $datosManifestacion->persona_consulta ?? [];
        foreach ($personasConsulta as $persona) {
            $personaNode = $xml->addChild('personaConsulta');
            $personaNode->addChild('rfc', strtoupper($persona['rfc'] ?? ''));
            $personaNode->addChild('tipoFigura', $persona['tipo_figura'] ?? '');
        }

        // eDocuments
        foreach ($documentos as $doc) {
            $edocNode = $xml->addChild('documentos');
            $edocNode->addChild('eDocument', $this->mveService->normalizeEdocumentFolio($doc['folio_edocument'] ?? ''));
        }

        // Información COVE
        $informacionCoveData = $informacionCove->informacion_cove ?? [];
        foreach ($informacionCoveData as $cove) {
            $coveNode = $xml->addChild('informacionCove');
            $coveNode->addChild('numeroCove', $cove['numero_cove'] ?? $cove['cove'] ?? '');
            $coveNode->addChild('incoterm', $cove['incoterm'] ?? '');
            $coveNode->addChild('vinculacion', $cove['vinculacion'] ?? $datosManifestacion->existe_vinculacion ?? '');
            
            // Pedimentos
            $pedimentos = $informacionCove->pedimentos ?? [];
            foreach ($pedimentos as $ped) {
                $pedNode = $coveNode->addChild('pedimentos');
                $pedNode->addChild('numero', $ped['pedimento'] ?? $ped['numero'] ?? '');
                $pedNode->addChild('patente', $ped['patente'] ?? '');
                $pedNode->addChild('aduana', $ped['aduana'] ?? '');
            }

            $coveNode->addChild('metodoValoracion', $cove['metodo_valoracion'] ?? $datosManifestacion->metodo_valoracion ?? '');
        }

        // Valor en aduana
        $valorData = $informacionCove->valor_en_aduana ?? [];
        $valorNode = $xml->addChild('valorAduana');
        $valorNode->addChild('totalPrecioPagado', $valorData['total_precio_pagado'] ?? '');
        $valorNode->addChild('totalPrecioPorPagar', $valorData['total_precio_por_pagar'] ?? '');
        $valorNode->addChild('totalIncrementables', $valorData['total_incrementables'] ?? '');
        $valorNode->addChild('totalDecrementables', $valorData['total_decrementables'] ?? '');
        $valorNode->addChild('totalValorAduana', $valorData['total_valor_aduana'] ?? '');

        // Sello digital
        $xml->addChild('selloDigital', $selloDigital);
        $xml->addChild('certificado', $certificado);

        return $xml->asXML();
    }

    /**
     * Enviar XML a VUCEM usando cURL (más robusto que SoapClient)
     * VUCEM tiene problemas con sus esquemas XSD que causan errores en SoapClient
     */
    private function enviarAVucem(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        string $xmlManifestacion
    ): array {
        try {
            $endpoint = config('vucem.mv_endpoint');
            $rfcFirmante = strtoupper($applicant->applicant_rfc);
            
            // Construir el envelope SOAP con WS-Security
            $soapEnvelope = $this->buildSoapEnvelope($xmlManifestacion, $rfcFirmante);

            Log::info('MVE - Enviando a VUCEM via cURL', [
                'endpoint' => $endpoint,
                'applicant_id' => $applicant->id,
                'rfc' => $rfcFirmante
            ]);

            // Enviar con cURL
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
                    'SOAPAction: "registroManifestacion"',
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
                $xmlManifestacion,
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
     * Construir envelope SOAP con WS-Security para VUCEM
     */
    private function buildSoapEnvelope(string $xmlManifestacion, string $rfcFirmante): string
    {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:ws="http://ws.ingresomanifestacion.manifestacion.www.ventanillaunica.gob.mx">
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
            <request><![CDATA[' . $xmlManifestacion . ']]></request>
        </ws:registroManifestacion>
    </soapenv:Body>
</soapenv:Envelope>';
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

            // Crear acuse
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

            // Actualizar status de las tablas originales
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
