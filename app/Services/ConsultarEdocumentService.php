<?php

namespace App\Services;

use SoapClient;
use SoapFault;
use Exception;
use Illuminate\Support\Facades\Log;

class ConsultarEdocumentService
{
    private SoapClient $soapClient;
    private string $endpoint;
    private EFirmaService $efirmaService;
    private ?string $rfc = null;
    private ?string $claveWebService = null;
    private array $debugInfo = [];

    // URL exacta del WSDL
    private const SOAP_ACTION = 'http://www.ventanillaunica.gob.mx/cove/ws/service/ConsultarEdocument';

    public function __construct(EFirmaService $efirmaService)
    {
        $this->endpoint = config('vucem.edocument.endpoint', 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEdocumentService');
        $this->efirmaService = $efirmaService;
        
        try {
            $this->initializeSoapClient();
        } catch (Exception $e) {
            Log::error('[EDOCUMENT] Error constructor: ' . $e->getMessage());
        }
    }

    private function initializeSoapClient(): void
    {
        $wsdlPath = config('vucem.edocument.wsdl_path');
        // Fallback si no existe local
        if (!file_exists($wsdlPath)) {
            $wsdlPath = config('vucem.edocument.wsdl_url', $this->endpoint . '?wsdl');
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT, // Indispensable
                'ciphers' => 'DEFAULT@SECLEVEL=1',
            ],
            'http' => [
                'timeout' => 120,
                'user_agent' => 'PHP-SOAP-Client'
            ]
        ]);

        $this->soapClient = new SoapClient($wsdlPath, [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_DISK,
            'soap_version' => SOAP_1_1,
            'connection_timeout' => 120,
            'location' => $this->endpoint,
            'stream_context' => $context,
            'keep_alive' => false,
        ]);
    }

    public function consultarEdocument(
        string $eDocument, 
        string $rfc, 
        string $claveWebService,
        string $certificadoPath,
        string $llavePrivadaPath, 
        string $passwordLlave,
        ?string $numeroAdenda = null
    ): array {
        try {
            // Limpieza de inputs (VUCEM falla con espacios extra)
            $this->rfc = trim($rfc);
            $this->claveWebService = trim($claveWebService);
            $eDocument = trim($eDocument);

            Log::info('[EDOCUMENT] Iniciando consulta XML Manual', ['eDocument' => $eDocument]);

            // =========================================================================
            // CORRECCIÓN DE LA CADENA ORIGINAL
            // =========================================================================
            // Regla VUCEM: La cadena debe ser |RFC|eDocument| (pipes al inicio y fin)
            // Si hay adenda: |RFC|eDocument|Adenda|
            
            $datosCadena = [$this->rfc, $eDocument];
            
            if ($numeroAdenda) {
                $datosCadena[] = trim($numeroAdenda);
            }

            // Construir la cadena con pipes
            $cadenaOriginal = '|' . implode('|', $datosCadena) . '|';
            
            Log::info('[EDOCUMENT] Cadena Original Calculada: ' . $cadenaOriginal);

            // 1. Generar Firma con la cadena correcta
            $firma = $this->efirmaService->generarFirmaElectronicaConArchivos(
                $cadenaOriginal, 
                $this->rfc, 
                $certificadoPath, 
                $llavePrivadaPath, 
                $passwordLlave
            );

            // 2. Header de Seguridad (Timestamp + Token)
            $this->setSecurityHeader();

            // 3. CONSTRUCCIÓN MANUAL DEL XML
            $xmlAdenda = '';
            if ($numeroAdenda) {
                $xmlAdenda = '<ns1:numeroAdenda>' . trim($numeroAdenda) . '</ns1:numeroAdenda>';
            }

            $requestBodyXml = 
                '<ns1:ConsultarEdocumentRequest xmlns:ns1="http://www.ventanillaunica.gob.mx/ConsultarEdocument/" xmlns:oxml="http://www.ventanillaunica.gob.mx/cove/ws/oxml/">' .
                    '<ns1:request>' .
                        '<ns1:firmaElectronica>' .
                            '<oxml:certificado>' . $firma['certificado'] . '</oxml:certificado>' .
                            '<oxml:cadenaOriginal>' . $firma['cadenaOriginal'] . '</oxml:cadenaOriginal>' .
                            '<oxml:firma>' . $firma['firma'] . '</oxml:firma>' .
                        '</ns1:firmaElectronica>' .
                        '<ns1:criterioBusqueda>' .
                            '<ns1:eDocument>' . $eDocument . '</ns1:eDocument>' .
                            $xmlAdenda .
                        '</ns1:criterioBusqueda>' .
                    '</ns1:request>' .
                '</ns1:ConsultarEdocumentRequest>';

            // Empaquetar XML crudo
            $soapVar = new \SoapVar($requestBodyXml, XSD_ANYXML);

            // 4. Llamada SOAP
            $response = $this->soapClient->__soapCall('ConsultarEdocument', [$soapVar], [
                'soapaction' => self::SOAP_ACTION
            ]);

            // Debug info interno
            $this->debugInfo = [
                'last_request' => $this->soapClient->__getLastRequest(),
                'last_response' => $this->soapClient->__getLastResponse()
            ];

            // =================================================================
            // 🛑 DIAGNÓSTICO VUCEM (AQUÍ ESTÁ LA MAGIA)
            // =================================================================
            Log::info('🔍 --- INICIO DIAGNÓSTICO DE RESPUESTA VUCEM ---');
            Log::info('Folio eDocument: ' . $eDocument);
            
            // 1. Logueamos el objeto PHP tal cual lo entendió SoapClient
            Log::info('Objeto PHP Mapeado:', ['data' => json_decode(json_encode($response), true)]);

            // 2. Logueamos el XML CRUDO (Raw)
            // Esto es vital porque a veces PHP falla al mapear arrays de objetos anidados
            try {
                $rawXml = $this->soapClient->__getLastResponse();
                // Lo guardamos en el log. Si es muy grande, VUCEM suele mandar base64, 
                // así que veremos un string largo, pero buscaremos las etiquetas <contenido> o <Archivo>
                Log::info('XML RAW VUCEM:', ['xml' => $rawXml]); 
            } catch (Exception $e) {
                Log::warning('No se pudo obtener el XML raw: ' . $e->getMessage());
            }
            Log::info('🔍 --- FIN DIAGNÓSTICO DE RESPUESTA VUCEM ---');
            // =================================================================
            
            return $this->processResponse($response, $eDocument);

        } catch (SoapFault $e) {
            Log::error('[EDOCUMENT] SOAP Fault: ' . $e->getMessage(), [
                'code' => $e->faultcode ?? 'N/A',
                'detail' => $e->detail ?? 'N/A'
            ]);
            return ['success' => false, 'message' => "Error VUCEM: " . $e->getMessage()];
        } catch (Exception $e) {
            Log::error('[EDOCUMENT] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function setSecurityHeader(): void
    {
        // Timestamp requerido por política de seguridad
        $created = gmdate("Y-m-d\TH:i:s\Z");
        $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

        // UsernameToken con la Clave de Servicios Web (Texto Plano)
        $securityXML = 
            '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">' .
                '<wsu:Timestamp wsu:Id="TS-1">' .
                    '<wsu:Created>' . $created . '</wsu:Created>' .
                    '<wsu:Expires>' . $expires . '</wsu:Expires>' .
                '</wsu:Timestamp>' .
                '<wsse:UsernameToken wsu:Id="UsernameToken-1">' .
                    '<wsse:Username>' . htmlspecialchars($this->rfc) . '</wsse:Username>' .
                    '<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . 
                        htmlspecialchars($this->claveWebService) . 
                    '</wsse:Password>' .
                '</wsse:UsernameToken>' .
            '</wsse:Security>';

        $this->soapClient->__setSoapHeaders([
            new \SoapHeader(
                'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 
                'Security', 
                new \SoapVar($securityXML, XSD_ANYXML)
            )
        ]);
    }

    private function processResponse($response, string $eDocument): array
    {
        $data = $response->response ?? $response;

        if (isset($data->contieneError) && $data->contieneError) {
            $msg = $data->mensaje ?? 'Error desconocido en VUCEM';
            if (isset($data->errores) && isset($data->errores->error)) {
                $detalles = is_array($data->errores->error) ? implode(', ', $data->errores->error) : $data->errores->error;
                $msg .= " Detalle: $detalles";
            }
            return ['success' => false, 'message' => $msg];
        }

        // Estructura base de éxito
        $result = [
            'success' => true,
            'message' => 'Consulta exitosa',
            'eDocument' => $eDocument,
            'archivos' => [],
            'cove_data' => []
        ];

        // 1. Extraer Datos COVE
        if (isset($data->resultadoBusqueda->cove)) {
            $result['cove_data'] = json_decode(json_encode($data->resultadoBusqueda->cove), true);
            $result['message'] = 'Datos del COVE recuperados correctamente.';
        }

        // 2. Buscar archivos adjuntos (Modificado para ser más agresivo buscando)
        // VUCEM a veces cambia los nombres de las propiedades
        $posiblesCampos = ['archivos', 'adjuntos', 'documentos', 'Archivo', 'archivo']; 
        $listaArchivos = [];

        if (isset($data->resultadoBusqueda)) {
            // Caso A: Propiedad directa
            foreach ($posiblesCampos as $campo) {
                if (isset($data->resultadoBusqueda->$campo)) {
                    $items = $data->resultadoBusqueda->$campo;
                    if (is_object($items)) $items = [$items]; // Un solo archivo
                    if (is_array($items)) $listaArchivos = array_merge($listaArchivos, $items);
                }
            }
        }
        
        // Caso B: A veces viene directo en resultadoBusqueda (sin sub-propiedad)
        // si es una digitalización pura.
        if (empty($listaArchivos) && isset($data->resultadoBusqueda->nombre) && isset($data->resultadoBusqueda->contenido)) {
             $listaArchivos[] = $data->resultadoBusqueda;
        }

        foreach ($listaArchivos as $archivo) {
            // Normalizar nombres de propiedades (VUCEM mezcla mayúsculas/minúsculas)
            $contenido = $archivo->contenido ?? $archivo->Contenido ?? null;
            $nombre = $archivo->nombre ?? $archivo->Nombre ?? 'documento_vucem.pdf';
            $tipo = $archivo->tipo ?? $archivo->Tipo ?? 'application/pdf';

            if ($contenido) {
                $result['archivos'][] = [
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'contenido' => $contenido,
                    'tamano' => $archivo->tamano ?? strlen($contenido)
                ];
            }
        }

        if (empty($result['archivos']) && !empty($result['cove_data'])) {
            $result['message'] .= ' (Sin PDF adjunto, solo datos XML).';
        }

        return $result;
    }
    
    public function getDebugInfo(): array
    {
        return $this->debugInfo;
    }
}