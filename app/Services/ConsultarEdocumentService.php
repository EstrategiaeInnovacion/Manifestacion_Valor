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
                $cadenaOriginal, // <--- AQUÍ ESTABA EL ERROR (Antes enviabas solo $eDocument)
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

            // Debug de éxito
            $this->debugInfo = [
                'last_request' => $this->soapClient->__getLastRequest(),
                'last_response' => $this->soapClient->__getLastResponse()
            ];
            
            return $this->processResponse($response, $eDocument);

        } catch (SoapFault $e) {
            Log::error('[EDOCUMENT] SOAP Fault: ' . $e->getMessage(), ['code' => $e->faultcode ?? 'N/A']);
            return ['success' => false, 'message' => "Error VUCEM: " . $e->getMessage()];
        } catch (Exception $e) {
            Log::error('[EDOCUMENT] Exception: ' . $e->getMessage());
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

        // 1. Extraer Datos COVE (Lo más importante)
        if (isset($data->resultadoBusqueda->cove)) {
            $result['cove_data'] = json_decode(json_encode($data->resultadoBusqueda->cove), true);
            $result['message'] = 'Datos del COVE recuperados correctamente.';
        }

        // 2. Buscar archivos adjuntos (Si los hubiera)
        $posiblesCampos = ['archivos', 'adjuntos', 'documentos'];
        $listaArchivos = [];

        if (isset($data->resultadoBusqueda)) {
            foreach ($posiblesCampos as $campo) {
                if (isset($data->resultadoBusqueda->$campo)) {
                    $items = $data->resultadoBusqueda->$campo;
                    if (is_object($items)) $items = [$items];
                    if (is_array($items)) $listaArchivos = array_merge($listaArchivos, $items);
                }
            }
        }

        foreach ($listaArchivos as $archivo) {
            if (isset($archivo->contenido)) {
                $result['archivos'][] = [
                    'nombre' => $archivo->nombre ?? 'archivo.pdf',
                    'tipo' => $archivo->tipo ?? 'application/pdf',
                    'contenido' => $archivo->contenido,
                    'tamano' => $archivo->tamano ?? 0
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