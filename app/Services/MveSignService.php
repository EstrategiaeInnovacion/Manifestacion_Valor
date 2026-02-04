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

    public function __construct()
    {
        $this->mveService = new ManifestacionValorService();
    }

    /**
     * Firmar y enviar Manifestación de Valor a VUCEM
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

            // 2. Firmar la cadena original con el certificado
            $selloDigital = $this->generarSelloDigital(
                $cadenaOriginal,
                $certificatePath,
                $privateKeyPath,
                $privateKeyPassword
            );

            if (!$selloDigital) {
                throw new \Exception('Error al generar el sello digital');
            }

            // 3. Obtener el certificado en Base64
            $certificadoBase64 = $this->getCertificadoBase64($certificatePath);

            // 4. Construir el XML para enviar a VUCEM
            $xmlManifestacion = $this->buildXmlManifestacion(
                $applicant,
                $datosManifestacion,
                $informacionCove,
                $documentos->documentos ?? [],
                $cadenaOriginal,
                $selloDigital,
                $certificadoBase64
            );

            Log::info('MVE - XML construido', [
                'applicant_id' => $applicant->id,
                'xml_size' => strlen($xmlManifestacion)
            ]);

            // 5. Enviar a VUCEM
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
     * Generar sello digital (firma)
     */
    private function generarSelloDigital(
        string $cadenaOriginal,
        string $certificatePath,
        string $privateKeyPath,
        string $privateKeyPassword
    ): ?string {
        try {
            // Leer la llave privada
            $privateKey = file_get_contents($privateKeyPath);
            if (!$privateKey) {
                throw new \Exception('No se pudo leer la llave privada');
            }

            // Abrir la llave privada con la contraseña
            $pkeyid = openssl_pkey_get_private($privateKey, $privateKeyPassword);
            if (!$pkeyid) {
                throw new \Exception('No se pudo abrir la llave privada. Verifique la contraseña.');
            }

            // Firmar la cadena original
            $signature = '';
            $success = openssl_sign($cadenaOriginal, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
            
            openssl_free_key($pkeyid);

            if (!$success) {
                throw new \Exception('Error al generar la firma digital');
            }

            // Convertir a Base64
            return base64_encode($signature);

        } catch (\Exception $e) {
            Log::error('MVE - Error al generar sello digital', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtener certificado en Base64
     */
    private function getCertificadoBase64(string $certificatePath): string
    {
        $cert = file_get_contents($certificatePath);
        
        // Extraer solo el contenido del certificado (sin BEGIN/END CERTIFICATE)
        $cert = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $cert);
        $cert = preg_replace('/-----END CERTIFICATE-----/', '', $cert);
        $cert = preg_replace('/\s+/', '', $cert);
        
        return $cert;
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
     * Enviar XML a VUCEM
     */
    private function enviarAVucem(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        string $xmlManifestacion
    ): array {
        try {
            $endpoint = config('vucem.mv_endpoint');
            $wsdl = config('vucem.mv_wsdl');

            $options = [
                'soap_version' => SOAP_1_1,
                'exceptions' => true,
                'trace' => 1,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => config('vucem.soap_timeout', 30),
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])
            ];

            $client = new SoapClient($wsdl, $options);

            $request = [
                'request' => [
                    'xmlManifestacion' => $xmlManifestacion
                ]
            ];

            Log::info('MVE - Enviando a VUCEM', [
                'endpoint' => $endpoint,
                'applicant_id' => $applicant->id
            ]);

            $response = $client->IngresoManifestacion($request);

            Log::info('MVE - Respuesta de VUCEM recibida', [
                'applicant_id' => $applicant->id
            ]);

            // Procesar respuesta
            return $this->procesarRespuestaVucem(
                $applicant,
                $datosManifestacion,
                $xmlManifestacion,
                $response
            );

        } catch (SoapFault $e) {
            Log::error('MVE - Error SOAP', [
                'message' => $e->getMessage(),
                'faultcode' => $e->faultcode,
                'faultstring' => $e->faultstring
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con VUCEM: ' . $e->getMessage()
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

        return [
            'success' => true,
            'message' => 'Manifestación generada correctamente (MODO PRUEBA)',
            'folio' => $folioSimulado,
            'acuse_id' => $acuse->id,
            'modo' => 'prueba'
        ];
    }

    /**
     * Procesar respuesta de VUCEM
     */
    private function procesarRespuestaVucem(
        MvClientApplicant $applicant,
        MvDatosManifestacion $datosManifestacion,
        string $xmlEnviado,
        $response
    ): array {
        try {
            $xmlRespuesta = $response->response->xmlRespuesta ?? '';
            $folio = $response->response->folioManifestacion ?? '';
            $status = $response->response->status ?? 'DESCONOCIDO';
            $mensaje = $response->response->mensaje ?? '';

            $acuse = MvAcuse::create([
                'applicant_id' => $applicant->id,
                'datos_manifestacion_id' => $datosManifestacion->id,
                'folio_manifestacion' => $folio,
                'numero_pedimento' => $datosManifestacion->pedimento,
                'xml_enviado' => $xmlEnviado,
                'xml_respuesta' => $xmlRespuesta,
                'status' => $status,
                'mensaje_vucem' => $mensaje,
                'fecha_envio' => now(),
                'fecha_respuesta' => now(),
            ]);

            return [
                'success' => $status === 'ACEPTADO',
                'message' => $mensaje,
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
                'message' => 'Error al procesar la respuesta de VUCEM'
            ];
        }
    }
}
