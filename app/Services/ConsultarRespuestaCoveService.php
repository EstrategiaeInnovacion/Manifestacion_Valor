<?php

namespace App\Services;

use App\Traits\VucemConnectivityHandler;
use Illuminate\Support\Facades\Log;
use Exception;

class ConsultarRespuestaCoveService
{
    use VucemConnectivityHandler;

    private const NS_WS = 'http://www.ventanillaunica.gob.mx/ConsultarRespuestaCove';
    private const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';

    /**
     * Consulta la respuesta de una operación COVE
     */
    public function consultarPorNumeroOperacion(
        string $numeroOperacion,
        string $rfc,
        string $claveWebService
    ): array {
        try {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="' . self::NS_SOAP . '" xmlns:con="' . self::NS_WS . '">
   <soapenv:Header/>
   <soapenv:Body>
      <con:solicitarConsultarRespuestaCove>
         <con:numeroOperacion>' . htmlspecialchars($numeroOperacion) . '</con:numeroOperacion>
      </con:solicitarConsultarRespuestaCove>
   </soapenv:Body>
</soapenv:Envelope>';

            $endpoint = config('vucem.consultar_respuesta_cove.endpoint');
            if (!$endpoint) {
                return ['success' => false, 'message' => 'Falta configurar vucem.consultar_respuesta_cove.endpoint'];
            }

            Log::info('[COVE_POLL] Consultando número de operación a VUCEM', [
                'numeroOperacion' => $numeroOperacion,
                'endpoint' => $endpoint
            ]);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_TIMEOUT => config('vucem.soap_timeout', 60),
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=0',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "http://www.ventanillaunica.gob.mx/ConsultarRespuestaCove"',
                    'Content-Length: ' . strlen($xml)
                ]
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return array_merge(
                    $this->handleCurlError($curlError, 'COVE_POLL'),
                    ['eDocument' => null]
                );
            }

            return $this->parseResponse($responseBody);

        } catch (Exception $e) {
            return array_merge(
                $this->handleConnectionException($e, 'COVE_POLL'),
                ['eDocument' => null]
            );
        }
    }

    private function parseResponse(string $responseBody): array
    {
        $result = [
            'success' => false,
            'message' => 'Respuesta no procesada correctamente',
            'status' => 'PENDIENTE', // Puede ser PENDIENTE, EXITOSO, ERROR
            'eDocument' => null,
            'errores' => [],
            'raw_response' => mb_substr($responseBody, 0, 1000)
        ];

        try {
            // Verificar si hay errores explícitos
            if (preg_match_all('/<errores>.*?<numeroFacturaOriginal>(.*?)<\/numeroFacturaOriginal>.*?<codigoError>(.*?)<\/codigoError>.*?<descripcionError>(.*?)<\/descripcionError>.*?<\/errores>/s', $responseBody, $errorMatches, PREG_SET_ORDER)) {
                foreach ($errorMatches as $error) {
                    $result['errores'][] = [
                        'factura' => $error[1],
                        'codigo' => $error[2],
                        'descripcion' => $error[3]
                    ];
                }
                
                // Si hay otro tipo de estructura de error
            } elseif (preg_match_all('/<errores>.*?<codigoError>(.*?)<\/codigoError>.*?<descripcionError>(.*?)<\/descripcionError>.*?<\/errores>/s', $responseBody, $errorMatches, PREG_SET_ORDER)) {
                 foreach ($errorMatches as $error) {
                    $result['errores'][] = [
                        'factura' => null,
                        'codigo' => $error[1],
                        'descripcion' => $error[2]
                    ];
                }
            }

            if (!empty($result['errores'])) {
                $result['status'] = 'ERROR';
                $result['message'] = 'VUCEM rechazó la operación: ' . $result['errores'][0]['descripcion'];
            }

            // Buscar eDocument devuelto
            if (preg_match('/<respuestasCove>.*?<eDocument>(.*?)<\/eDocument>.*?<\/respuestasCove>/s', $responseBody, $match)) {
                $result['eDocument'] = $match[1];
                $result['success'] = true;
                $result['status'] = 'EXITOSO';
                $result['message'] = 'Operación procesada con éxito. eDocument: ' . $match[1];
            } elseif (preg_match('/<eDocument>(.*?)<\/eDocument>/', $responseBody, $match)) {
                // Caso simplificado por si falla el outer match
                $result['eDocument'] = $match[1];
                $result['success'] = true;
                $result['status'] = 'EXITOSO';
                $result['message'] = 'Operación procesada con éxito. eDocument: ' . $match[1];
            } else {
                 if (empty($result['errores']) && strpos($responseBody, 'No se encontraron resultados') === false) {
                     // Si no hay eDocument y no hay error claro, puede que siga procesando
                     $result['status'] = 'PENDIENTE';
                     $result['message'] = 'VUCEM sigue procesando la operación.';
                 }
            }

        } catch (Exception $e) {
            $result['message'] = 'Excepción parseando la respuesta COVE: ' . $e->getMessage();
        }

        return $result;
    }
}
