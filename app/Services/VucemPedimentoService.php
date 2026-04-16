<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class VucemPedimentoService
{
    private EFirmaService $efirmaService;
    private string $listarEndpoint;
    private string $consultarCompletoEndpoint;

    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const NS_WSU = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    private const PASSWORD_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';

    public function __construct(EFirmaService $efirmaService)
    {
        $this->efirmaService = $efirmaService;
        
        $this->listarEndpoint = config('vucem.pedimentos.listar_endpoint', 
            'https://www.ventanillaunica.gob.mx/ventanilla-ws-pedimentos/ListarPedimentosService');
        
        $this->consultarCompletoEndpoint = config('vucem.pedimentos.consultar_completo_endpoint',
            'https://www.ventanillaunica.gob.mx/ventanilla-ws-pedimentos/ConsultarPedimentoCompletoService');
    }

    /**
     * Listar pedimentos del solicitante
     *
     * @param string $rfc
     * @param string $claveWebService
     * @param string $certificadoPath
     * @param string $llavePrivadaPath
     * @param string $passwordLlave
     * @return array
     */
    public function listarPedimentos(
        string $rfc,
        string $claveWebService,
        string $certificadoPath,
        string $llavePrivadaPath,
        string $passwordLlave
    ): array {
        try {
            $rfcClean = strtoupper(trim($rfc));

            $xml = $this->buildListarPedimentosXml($rfcClean, $claveWebService);

            Log::info('[PEDIMENTOS] Listar pedimentos', [
                'rfc' => $rfcClean,
                'endpoint' => $this->listarEndpoint
            ]);

            $response = $this->sendSoapRequest($xml, $this->listarEndpoint);

            return $this->parseListarPedimentosResponse($response);

        } catch (Exception $e) {
            Log::error('[PEDIMENTOS] Error listarPedimentos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Consultar pedimento completo por número
     *
     * @param string $numeroPedimento
     * @param string|null $patente
     * @param string|null $aduana
     * @param string $rfc
     * @param string $claveWebService
     * @param string $certificadoPath
     * @param string $llavePrivadaPath
     * @param string $passwordLlave
     * @return array
     */
    public function consultarPedimento(
        string $numeroPedimento,
        ?string $patente,
        ?string $aduana,
        string $rfc,
        string $claveWebService,
        string $certificadoPath,
        string $llavePrivadaPath,
        string $passwordLlave
    ): array {
        try {
            $rfcClean = strtoupper(trim($rfc));
            $pedimentoClean = trim($numeroPedimento);
            $patenteClean = $patente ? trim($patente) : null;
            $aduanaClean = $aduana ? trim($aduana) : null;

            $xml = $this->buildConsultarPedimentoXml(
                $pedimentoClean,
                $patenteClean,
                $aduanaClean,
                $rfcClean,
                $claveWebService
            );

            Log::info('[PEDIMENTOS] Consultar pedimento', [
                'rfc' => $rfcClean,
                'pedimento' => $pedimentoClean,
                'patente' => $patenteClean,
                'aduana' => $aduanaClean,
                'endpoint' => $this->consultarCompletoEndpoint
            ]);

            $response = $this->sendSoapRequest($xml, $this->consultarCompletoEndpoint);

            return $this->parseConsultarPedimentoResponse($response, $pedimentoClean);

        } catch (Exception $e) {
            Log::error('[PEDIMENTOS] Error consultarPedimento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Construir XML para listar pedimentos
     *
     * @param string $rfc
     * @param string $claveWebService
     * @return string
     */
    private function buildListarPedimentosXml(string $rfc, string $claveWebService): string
    {
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:ped="http://ws.pedimentos.ventanillaunica.gob.mx">
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
      <ped:listarPedimentos>
         <ped:rfc>' . $rfc . '</ped:rfc>
      </ped:listarPedimentos>
   </soapenv:Body>
</soapenv:Envelope>';

        return $xml;
    }

    /**
     * Construir XML para consultar pedimento completo
     *
     * @param string $numeroPedimento
     * @param string|null $patente
     * @param string|null $aduana
     * @param string $rfc
     * @param string $claveWebService
     * @return string
     */
    private function buildConsultarPedimentoXml(
        string $numeroPedimento,
        ?string $patente,
        ?string $aduana,
        string $rfc,
        string $claveWebService
    ): string {
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));

        $pedimentoXml = '<ped:numeroPedimento>' . htmlspecialchars($numeroPedimento, ENT_XML1) . '</ped:numeroPedimento>';
        
        if ($patente) {
            $pedimentoXml .= '<ped:patente>' . htmlspecialchars($patente, ENT_XML1) . '</ped:patente>';
        }
        
        if ($aduana) {
            $pedimentoXml .= '<ped:aduana>' . htmlspecialchars($aduana, ENT_XML1) . '</ped:aduana>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:ped="http://ws.pedimentos.ventanillaunica.gob.mx">
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
      <ped:consultarPedimentoCompleto>
         ' . $pedimentoXml . '
         <ped:rfc>' . $rfc . '</ped:rfc>
      </ped:consultarPedimentoCompleto>
   </soapenv:Body>
</soapenv:Envelope>';

        return $xml;
    }

    /**
     * Enviar petición SOAP
     *
     * @param string $xml
     * @param string $endpoint
     * @return string
     */
    private function sendSoapRequest(string $xml, string $endpoint): string
    {
        $ch = curl_init($endpoint);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_TIMEOUT => config('vucem.soap_timeout', 120),
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
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
            throw new Exception('Error de conexión: ' . $error);
        }

        Log::info('[PEDIMENTOS] Respuesta HTTP', [
            'status' => $httpCode,
            'length' => strlen($responseBody)
        ]);

        return $responseBody;
    }

    /**
     * Parsear respuesta de listar pedimentos
     *
     * @param string $responseBody
     * @return array
     */
    private function parseListarPedimentosResponse(string $responseBody): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];

        try {
            if (preg_match('/<[:\w]*faultcode>(.*?)<\/[:\w]*faultcode>/', $responseBody, $matches)) {
                $result['message'] = 'Error SOAP: ' . trim($matches[1]);
                return $result;
            }

            $pedimentos = [];
            
            if (preg_match_all('/<pedimento>(.*?)<\/pedimento>/s', $responseBody, $matches)) {
                foreach ($matches[1] as $pedimentoXml) {
                    $pedimento = [];
                    
                    if (preg_match('/<numero>(.*?)<\/numero>/', $pedimentoXml, $m)) {
                        $pedimento['numero'] = trim($m[1]);
                    }
                    if (preg_match('/<patente>(.*?)<\/patente>/', $pedimentoXml, $m)) {
                        $pedimento['patente'] = trim($m[1]);
                    }
                    if (preg_match('/<aduana>(.*?)<\/aduana>/', $pedimentoXml, $m)) {
                        $pedimento['aduana'] = trim($m[1]);
                    }
                    if (preg_match('/<tipoOperacion>(.*?)<\/tipoOperacion>/', $pedimentoXml, $m)) {
                        $pedimento['tipo_operacion'] = trim($m[1]);
                    }
                    if (preg_match('/<fecha>(.*?)<\/fecha>/', $pedimentoXml, $m)) {
                        $pedimento['fecha'] = trim($m[1]);
                    }
                    if (preg_match('/<status>(.*?)<\/status>/', $pedimentoXml, $m)) {
                        $pedimento['status'] = trim($m[1]);
                    }
                    
                    if (!empty($pedimento['numero'])) {
                        $pedimentos[] = $pedimento;
                    }
                }
            }

            if (!empty($pedimentos)) {
                $result['success'] = true;
                $result['message'] = 'Se encontraron ' . count($pedimentos) . ' pedimento(s).';
                $result['data'] = ['pedimentos' => $pedimentos];
            } else {
                $result['message'] = 'No se encontraron pedimentos.';
                $result['data'] = ['pedimentos' => []];
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al parsear respuesta: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Parsear respuesta de consultar pedimento completo
     *
     * @param string $responseBody
     * @param string $numeroPedimento
     * @return array
     */
    private function parseConsultarPedimentoResponse(string $responseBody, string $numeroPedimento): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => []
        ];

        try {
            if (preg_match('/<[:\w]*faultcode>(.*?)<\/[:\w]*faultcode>/', $responseBody, $matches)) {
                $result['message'] = 'Error SOAP: ' . trim($matches[1]);
                return $result;
            }

            if (preg_match('/<[:\w]*mensaje>.*?<[:\w]*descripcionError>(.*?)<\/[:\w]*descripcionError>/s', $responseBody, $matches)) {
                $result['message'] = 'Error VUCEM: ' . trim($matches[1]);
                return $result;
            }

            $pedimento = [
                'numero' => $numeroPedimento,
            ];

            if (preg_match('/<patente>(.*?)<\/patente>/', $responseBody, $m)) {
                $pedimento['patente'] = trim($m[1]);
            }
            if (preg_match('/<aduana>(.*?)<\/aduana>/', $responseBody, $m)) {
                $pedimento['aduana'] = trim($m[1]);
            }
            if (preg_match('/<tipoOperacion>(.*?)<\/tipoOperacion>/', $responseBody, $m)) {
                $pedimento['tipo_operacion'] = trim($m[1]);
            }
            if (preg_match('/<fecha>(.*?)<\/fecha>/', $responseBody, $m)) {
                $pedimento['fecha'] = trim($m[1]);
            }
            if (preg_match('/<rfcImportador>(.*?)<\/rfcImportador>/', $responseBody, $m)) {
                $pedimento['rfc_importador'] = trim($m[1]);
            }
            if (preg_match('/<nombreImportador>(.*?)<\/nombreImportador>/', $responseBody, $m)) {
                $pedimento['nombre_importador'] = trim($m[1]);
            }
            if (preg_match('/<rfcAgenteAduanal>(.*?)<\/rfcAgenteAduanal>/', $responseBody, $m)) {
                $pedimento['rfc_agente_aduanal'] = trim($m[1]);
            }
            if (preg_match('/<nombreAgenteAduanal>(.*?)<\/nombreAgenteAduanal>/', $responseBody, $m)) {
                $pedimento['nombre_agente_aduanal'] = trim($m[1]);
            }
            if (preg_match('/<curpAgente>(.*?)<\/curpAgente>/', $responseBody, $m)) {
                $pedimento['curp_agente'] = trim($m[1]);
            }
            if (preg_match('/<clavePedimento>(.*?)<\/clavePedimento>/', $responseBody, $m)) {
                $pedimento['clave_pedimento'] = trim($m[1]);
            }
            if (preg_match('/<regimen>(.*?)<\/regimen>/', $responseBody, $m)) {
                $pedimento['regimen'] = trim($m[1]);
            }
            if (preg_match('/<destino>(.*?)<\/destino>/', $responseBody, $m)) {
                $pedimento['destino'] = trim($m[1]);
            }

            if (preg_match('/<mercancias>(.*?)<\/mercancias>/s', $responseBody, $m)) {
                $mercancias = [];
                if (preg_match_all('/<mercancia>(.*?)<\/mercancia>/s', $m[1], $mercMatches)) {
                    foreach ($mercMatches[1] as $mercXml) {
                        $mercancia = [];
                        
                        if (preg_match('/<fraccionArancelaria>(.*?)<\/fraccionArancelaria>/', $mercXml, $x)) {
                            $mercancia['fraccion_arancelaria'] = trim($x[1]);
                        }
                        if (preg_match('/<descripcion>(.*?)<\/descripcion>/', $mercXml, $x)) {
                            $mercancia['descripcion'] = trim($x[1]);
                        }
                        if (preg_match('/<valorAduana>(.*?)<\/valorAduana>/', $mercXml, $x)) {
                            $mercancia['valor_aduana'] = trim($x[1]);
                        }
                        if (preg_match('/<valorComercial>(.*?)<\/valorComercial>/', $mercXml, $x)) {
                            $mercancia['valor_comercial'] = trim($x[1]);
                        }
                        if (preg_match('/<cantidad>(.*?)<\/cantidad>/', $mercXml, $x)) {
                            $mercancia['cantidad'] = trim($x[1]);
                        }
                        if (preg_match('/<unidadMedida>(.*?)<\/unidadMedida>/', $mercXml, $x)) {
                            $mercancia['unidad_medida'] = trim($x[1]);
                        }
                        
                        if (!empty($mercancia['fraccion_arancelaria'])) {
                            $mercancias[] = $mercancia;
                        }
                    }
                }
                $pedimento['mercancias'] = $mercancias;
            }

            if (preg_match('/<impuestos>(.*?)<\/impuestos>/s', $responseBody, $m)) {
                $impuestos = [];
                if (preg_match_all('/<impuesto>(.*?)<\/impuesto>/s', $m[1], $impMatches)) {
                    foreach ($impMatches[1] as $impXml) {
                        $impuesto = [];
                        
                        if (preg_match('/<tipo>(.*?)<\/tipo>/', $impXml, $x)) {
                            $impuesto['tipo'] = trim($x[1]);
                        }
                        if (preg_match('/<tasa>(.*?)<\/tasa>/', $impXml, $x)) {
                            $impuesto['tasa'] = trim($x[1]);
                        }
                        if (preg_match('/<monto>(.*?)<\/monto>/', $impXml, $x)) {
                            $impuesto['monto'] = trim($x[1]);
                        }
                        
                        if (!empty($impuesto['tipo'])) {
                            $impuestos[] = $impuesto;
                        }
                    }
                }
                $pedimento['impuestos'] = $impuestos;
            }

            if (!empty($pedimento['rfc_importador'])) {
                $result['success'] = true;
                $result['message'] = 'Pedimento consultado exitosamente.';
                $result['data'] = ['pedimento' => $pedimento];
            } else {
                $result['message'] = 'No se encontró información del pedimento.';
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al parsear respuesta: ' . $e->getMessage();
        }

        return $result;
    }
}