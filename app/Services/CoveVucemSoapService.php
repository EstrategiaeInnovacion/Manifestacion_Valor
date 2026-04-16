<?php

namespace App\Services;

use App\Models\MvClientApplicant;
use App\Models\CoveDocument;
use App\Traits\VucemConnectivityHandler;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para construir y enviar XML SOAP de COVE a VUCEM
 * 
 * Namespace: http://www.ventanillaunica.gob.mx/cove/wsrecepcion
 */
class CoveVucemSoapService
{
    use VucemConnectivityHandler;

    private const NS_COVE = 'http://www.ventanillaunica.gob.mx/cove/wsrecepcion';
    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private const PASSWORD_TYPE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';

    /**
     * Genera el XML SOAP completo para enviar a VUCEM (RecibirCove)
     */
    public function buildRecibirCoveXml(
        MvClientApplicant $applicant,
        CoveDocument $coveDocument,
        string $rfc,
        string $claveWebService,
        array $firmaData = [],
        array $options = []
    ): array {
        $errors = [];
        $mapping = [];

        try {
            // Se asume que payload de CoveDocument es un array que lista comprobantes/facturas
            $covesData = $coveDocument->payload ?? [];
            
            // Trataremos el payload principal como un arreglo de "comprobantes"
            if (empty($covesData)) {
                $errors[] = 'No se encontró información en el payload para estructurar el COVE.';
            }

            $comprobantesXml = '';
            foreach ($covesData as $idx => $cove) {
                // Datos generales del comprobante
                $tipoOperacion = $cove['tipoOperacion'] ?? 'TOL';
                $patenteAduanal = $cove['patenteAduanal'] ?? '';
                $fechaExpedicion = $cove['fechaExpedicion'] ?? date('Y-m-d');
                $tipoFigura = $cove['tipoFigura'] ?? '1'; // 1 = Agente Aduanal
                $correoElectronico = $cove['correoElectronico'] ?? $applicant->applicant_email ?? '';

                $comprobanteXml = "<ws:tipoOperacion>{$tipoOperacion}</ws:tipoOperacion>";
                if (!empty($patenteAduanal)) {
                    $comprobanteXml .= "<ws:patenteAduanal>{$patenteAduanal}</ws:patenteAduanal>";
                }
                $comprobanteXml .= "<ws:fechaExpedicion>{$fechaExpedicion}</ws:fechaExpedicion>";
                $comprobanteXml .= "<ws:tipoFigura>{$tipoFigura}</ws:tipoFigura>";
                $comprobanteXml .= "<ws:correoElectronico>{$correoElectronico}</ws:correoElectronico>";

                // Facturas
                $facturas = $cove['facturas'] ?? []; 

                if (empty($facturas)) {
                    $errors[] = "Comprobante {$idx}: No se encontraron facturas asosiadas.";
                }

                $facturasXml = '';
                foreach ($facturas as $fIdx => $factura) {
                    $certificadoOrigen = $factura['certificadoOrigen'] ?? '0';
                    $subdivision = $factura['subdivision'] ?? '0';
                    $numeroFactura = $factura['factura'] ?? $factura['numeroFactura'] ?? 'S/N';
                    
                    $facturasXml .= "
                    <ws:facturas>
                       <ws:certificadoOrigen>{$certificadoOrigen}</ws:certificadoOrigen>
                       <ws:numeroFacturaOriginal>{$numeroFactura}</ws:numeroFacturaOriginal>
                       <ws:subdivision>{$subdivision}</ws:subdivision>";

                    // Emisor
                    $emisor = $factura['emisor'] ?? [];
                    if (!empty($emisor)) {
                        $tipoId = $emisor['tipoIdentificador'] ?? '1';
                        $ident = $emisor['identificacion'] ?? '';
                        $nombre = $emisor['nombre'] ?? '';
                        $facturasXml .= "
                        <ws:emisor>
                           <ws:tipoIdentificador>{$tipoId}</ws:tipoIdentificador>
                           <ws:identificacion>{$ident}</ws:identificacion>
                           <ws:nombre>{$nombre}</ws:nombre>";
                           // Domicilio (opcional en schema pero requerido en Mv)
                           if (!empty($emisor['domicilio'])) {
                               $facturasXml .= $this->buildDomicilioXml($emisor['domicilio'], 'emisor.');
                           }
                        $facturasXml .= "
                        </ws:emisor>";
                    }

                    // Destinatario
                    $destinatario = $factura['destinatario'] ?? [];
                    if (!empty($destinatario)) {
                        $tipoId = $destinatario['tipoIdentificador'] ?? '1';
                        $ident = $destinatario['identificacion'] ?? '';
                        $nombre = $destinatario['nombre'] ?? '';
                        $facturasXml .= "
                        <ws:destinatario>
                           <ws:tipoIdentificador>{$tipoId}</ws:tipoIdentificador>
                           <ws:identificacion>{$ident}</ws:identificacion>
                           <ws:nombre>{$nombre}</ws:nombre>";
                           if (!empty($destinatario['domicilio'])) {
                               $facturasXml .= $this->buildDomicilioXml($destinatario['domicilio'], 'destinatario.');
                           }
                        $facturasXml .= "
                        </ws:destinatario>";
                    }

                    // Mercancías
                    $mercancias = $factura['mercancias'] ?? [];
                    foreach ($mercancias as $mIdx => $mercancia) {
                        $descripcion = htmlspecialchars($mercancia['descripcionGenerica'] ?? $mercancia['descripcion'] ?? '');
                        $claveUnidad = $mercancia['claveUnidadMedida'] ?? $mercancia['unidad'] ?? '';
                        $cantidad = $this->formatDecimal($mercancia['cantidad'] ?? 0);
                        $valor = $this->formatDecimal($mercancia['valorUnitario'] ?? 0);
                        $valorTotal = $this->formatDecimal($mercancia['valorTotal'] ?? 0);
                        $moneda = $mercancia['tipoMoneda'] ?? 'USD';

                        $facturasXml .= "
                        <ws:mercancias>
                           <ws:descripcionGenerica>{$descripcion}</ws:descripcionGenerica>
                           <ws:claveUnidadMedida>{$claveUnidad}</ws:claveUnidadMedida>
                           <ws:cantidad>{$cantidad}</ws:cantidad>
                           <ws:valorUnitario>{$valor}</ws:valorUnitario>
                           <ws:valorTotal>{$valorTotal}</ws:valorTotal>
                           <ws:tipoMoneda>{$moneda}</ws:tipoMoneda>
                        </ws:mercancias>";
                    }

                    $facturasXml .= "
                    </ws:facturas>";
                }

                $comprobanteXml .= $facturasXml;
                
                $comprobantesXml .= "
                <ws:comprobantes>
                   {$comprobanteXml}
                </ws:comprobantes>";
            }

            // Firma electrónica
            $firmaXml = '';
            if (!empty($firmaData)) {
                $certificado = $firmaData['certificado'] ?? '';
                $firma = $firmaData['firma'] ?? '';
                $firmaXml = "
                <ws:firmaElectronica>
                   <certificado xmlns=\"http://www.ventanillaunica.gob.mx/cove/ws/oxml/\">{$certificado}</certificado>
                   <firma xmlns=\"http://www.ventanillaunica.gob.mx/cove/ws/oxml/\">{$firma}</firma>
                </ws:firmaElectronica>";
            }

            // Construir XML SOAP completo
            $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:ws="' . self::NS_COVE . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="' . self::NS_WSSE . '">
         <wsse:UsernameToken>
            <wsse:Username>' . htmlspecialchars($rfc) . '</wsse:Username>
            <wsse:Password Type="' . self::PASSWORD_TYPE . '">' . htmlspecialchars($claveWebService) . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <ws:solicitarRecibirCove>' . $comprobantesXml . $firmaXml . '
      </ws:solicitarRecibirCove>
   </soapenv:Body>
</soapenv:Envelope>';

            // Para COVE el enconding ISO-8859-1 debe respetarse, por lo que lo convertimos desde UTF-8
            $xmlIso = mb_convert_encoding($xml, 'ISO-8859-1', 'UTF-8');

            return [
                'success' => count($errors) === 0,
                'xml' => $xmlIso,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error('[COVE_SOAP] Error generando XML', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'xml' => '',
                'errors' => array_merge($errors, ['Exception: ' . $e->getMessage()])
            ];
        }
    }

    private function buildDomicilioXml(array $domicilio, string $prefix = ''): string
    {
        $calle = htmlspecialchars($domicilio['calle'] ?? '');
        $exterior = htmlspecialchars($domicilio['numeroExterior'] ?? '');
        $cp = htmlspecialchars($domicilio['codigoPostal'] ?? '');
        $pais = htmlspecialchars($domicilio['pais'] ?? '');

        return "
        <ws:domicilio>
           <ws:calle>{$calle}</ws:calle>
           <ws:numeroExterior>{$exterior}</ws:numeroExterior>
           <ws:codigoPostal>{$cp}</ws:codigoPostal>
           <ws:pais>{$pais}</ws:pais>
        </ws:domicilio>";
    }

    private function adaptarPreciosAFacturas(array $precios): array
    {
         // Adaptador dummy: En caso que reusen 'precio_pagado' de MVE como Facturas
         $facturas = [];
         foreach ($precios as $idx => $p) {
             $facturas[] = [
                 'factura' => 'F-' . ($idx + 1),
                 'certificadoOrigen' => '0',
                 'subdivision' => '0',
             ];
         }
         return $facturas;
    }

    private function formatDecimal($value): string
    {
        if (empty($value) || $value === '') {
            return '0.000';
        }
        $clean = preg_replace('/[,\s]/', '', (string)$value);
        if (!is_numeric($clean)) return '0.000';
        return number_format((float)$clean, 3, '.', '');
    }

    /**
     * Enviar XML a VUCEM (RecibirCoveService)
     */
    public function sendToVucem(
        string $xmlIso,
        string $rfc,
        string $claveWebService,
        bool $testMode = false
    ): array {
        $key = $testMode ? 'testing' : 'production';
        // Ajustamos la lectura a lo que configuramos (en MvVucem usa vucem.mv_endpoint abstracto)
        $endpoint = config("vucem.recibir_cove.endpoint");
        if (!$endpoint) {
           return ['success' => false, 'message' => 'Falta configurar vucem.recibir_cove.endpoint'];
        }

        Log::info('[COVE_SOAP] Iniciando envío a VUCEM', [
            'rfc' => $rfc,
            'test_mode' => $testMode,
            'endpoint' => $endpoint
        ]);

        try {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xmlIso, // Ya debe estar en ISO
                CURLOPT_TIMEOUT => config('vucem.soap_timeout', 120),
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=ISO-8859-1',
                    'SOAPAction: "http://www.ventanillaunica.gob.mx/cove/wsrecepcion/RecibirCove"',
                    'Content-Length: ' . strlen($xmlIso)
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return array_merge(
                    $this->handleCurlError($curlError, 'COVE_ENVIO'),
                    ['xml_sent' => $xmlIso, 'response' => null]
                );
            }

            Log::info('[COVE_SOAP] Respuesta recibida de VUCEM', [
                'status' => $httpCode,
                'body_length' => strlen($responseBody)
            ]);

            return $this->parseCoveResponse($responseBody, $xmlIso);

        } catch (Exception $e) {
            return array_merge(
                $this->handleConnectionException($e, 'COVE_ENVIO'),
                ['xml_sent' => $xmlIso, 'response' => null]
            );
        }
    }

    private function parseCoveResponse(string $responseBody, string $xmlSent): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'xml_sent' => $xmlSent,
            'response' => $responseBody,
            'numero_operacion' => null,
            'errores' => []
        ];

        try {
            // Convertimos la respuesta a UTF-8 para seguro Regexing en PHP
            $responseBodyUtf8 = mb_convert_encoding($responseBody, 'UTF-8', 'ISO-8859-1');

            if (preg_match('/<numeroOperacion>(.*?)<\/numeroOperacion>/', $responseBodyUtf8, $matches)) {
                $result['numero_operacion'] = $matches[1];
                $result['success'] = true;
                $result['message'] = 'Operación recibida. Número de operación: ' . $matches[1];
            }

            if (preg_match_all('/<errores>.*?<codigoError>(.*?)<\/codigoError>.*?<descripcionError>(.*?)<\/descripcionError>.*?<\/errores>/s', $responseBodyUtf8, $errorMatches, PREG_SET_ORDER)) {
                foreach ($errorMatches as $error) {
                    $result['errores'][] = [
                        'codigo' => $error[1],
                        'descripcion' => $error[2]
                    ];
                }
                
                if (!empty($result['errores'])) {
                    $result['success'] = false;
                    $result['message'] = 'VUCEM rechazó la solicitud: ' . $result['errores'][0]['descripcion'];
                    // Si trae leyendaError lo buscamos también
                    if (preg_match('/<leyendaError>(.*?)<\/leyendaError>/s', $responseBodyUtf8, $leyendaMatch)) {
                         $result['message'] .= ' - ' . $leyendaMatch[1];
                    }
                }
            }
            
            if (empty($result['message'])) {
                $result['message'] = 'Respuesta recibida (ver logs).';
            }

        } catch (Exception $e) {
            $result['message'] = 'Error al procesar respuesta: ' . $e->getMessage();
        }

        return $result;
    }
}
