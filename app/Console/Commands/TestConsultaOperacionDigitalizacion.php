<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MvClientApplicant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Test exhaustivo para consultar el resultado de una operación de digitalización en VUCEM.
 * 
 * Problema original: El polling usa el endpoint de REGISTRO (DigitalizarDocumentoService)
 * pero la CONSULTA debe ir a un servicio DIFERENTE (ConsultarEDocumentDigitalizarDocumentoService).
 * 
 * Este comando prueba múltiples combinaciones de:
 * - Endpoints (el servicio de consulta vs el de registro)
 * - Nombres de elementos XML (variaciones del WSDL)
 * - Namespaces
 * Para descubrir cuál es la combinación correcta.
 */
class TestConsultaOperacionDigitalizacion extends Command
{
    protected $signature = 'vucem:test-consulta-operacion 
                            {operacion : Número de operación VUCEM (ej: 313962613)} 
                            {--applicant=1 : ID del applicant en BD}
                            {--fetch-wsdl : Intentar descargar WSDLs para descubrir operaciones}';

    protected $description = 'Descubre y prueba el endpoint correcto para consultar operaciones de digitalización VUCEM';

    // ─── Endpoints candidatos ───
    private const ENDPOINTS = [
        // El endpoint CORRECTO para consultas (servicio separado)
        'ConsultarEDocument_puerto443' => 'https://www.ventanillaunica.gob.mx:443/ventanilla/ConsultarEDocumentDigitalizarDocumentoService',
        'ConsultarEDocument_sinPuerto' => 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEDocumentDigitalizarDocumentoService',
        // Variantes con HA (High Availability)
        'ConsultarEDocument_HA' => 'https://www.ventanillaunica.gob.mx/ventanilla-ws-HA/ConsultarEDocumentDigitalizarDocumentoService',
        // El endpoint erróneo (solo registro) - para confirmar que falla
        'Digitalizar_REGISTRO' => 'https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService',
    ];

    // ─── Variaciones de namespace + elemento a probar ───
    private const SOAP_VARIATIONS = [
        // Variación 1: Namespace de consulta con elemento estándar
        [
            'label' => 'NS consulta + consultarEDocumentRequest',
            'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/ConsultarEDocumentDigitalizarDocumento',
            'elemento' => 'consultarEDocumentRequest',
            'soap_action' => 'http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento',
        ],
        // Variación 2: Namespace consulta + consultaEDocumentDigitalizarDocumentoServiceRequest  
        [
            'label' => 'NS consulta + consultaEDocumentDigitalizarDocumentoServiceRequest',
            'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/ConsultarEDocumentDigitalizarDocumento',
            'elemento' => 'consultaEDocumentDigitalizarDocumentoServiceRequest',
            'soap_action' => 'http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento',
        ],
        // Variación 3: Namespace raíz digitalizar + petición consulta
        [
            'label' => 'NS digitalizar raíz + consultaEDocumentDigitalizarDocumentoServiceRequest',
            'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/',
            'elemento' => 'consultaEDocumentDigitalizarDocumentoServiceRequest',
            'soap_action' => '',
        ],
        // Variación 4: Namespace DigitalizarDocumento + consultarEdocumentDigitalizarDocumentoPeticion
        [
            'label' => 'NS DigitalizarDocumento + consultarEdocumentDigitalizarDocumentoPeticion',
            'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/DigitalizarDocumento',
            'elemento' => 'consultarEdocumentDigitalizarDocumentoPeticion',
            'soap_action' => '',
        ],
        // Variación 5: Namespace consulta + consultarEdocumentPeticion (simplificado)
        [
            'label' => 'NS consulta + consultarEdocumentPeticion',
            'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/ConsultarEDocumentDigitalizarDocumento',
            'elemento' => 'consultarEdocumentPeticion',
            'soap_action' => 'http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento',
        ],
        // Variación 6: Namespace consulta + consultaEDocumentPeticion
        [
            'label' => 'NS consulta + consultaEDocumentPeticion',
            'ns' => 'http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/ConsultarEDocumentDigitalizarDocumento',
            'elemento' => 'consultaEDocumentPeticion',
            'soap_action' => 'http://www.ventanillaunica.gob.mx/ConsultaEDocumentDigitalizarDocumento',
        ],
    ];

    public function handle(): int
    {
        $operacion = trim($this->argument('operacion'));
        $applicantId = (int) $this->option('applicant');
        $fetchWsdl = $this->option('fetch-wsdl');

        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║  TEST: Descubrimiento consulta operación digitalización VUCEM   ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("Número de operación: <fg=yellow>{$operacion}</>");

        // ─── Obtener credenciales ───
        $applicant = MvClientApplicant::find($applicantId);
        if (!$applicant) {
            $this->error("Applicant ID {$applicantId} no encontrado.");
            return self::FAILURE;
        }

        $rfc = strtoupper(trim($applicant->applicant_rfc));
        $claveWs = $applicant->vucem_webservice_key;

        $this->line("RFC: <fg=green>{$rfc}</>");
        $this->line("Clave WS: <fg=green>" . substr($claveWs, 0, 6) . '***' . substr($claveWs, -4) . "</> (" . strlen($claveWs) . " chars)");
        $this->newLine();

        // ─── Paso 1: Intentar descargar WSDLs ───
        if ($fetchWsdl) {
            $this->info('══ PASO 1: Descubrimiento de WSDLs ══');
            $this->fetchWsdls();
            $this->newLine();
        }

        // ─── Paso 2: Probar combinaciones endpoint × variación SOAP ───
        $this->info('══ PASO 2: Probando combinaciones Endpoint × Elemento SOAP ══');
        $this->newLine();

        $resultados = [];
        $exitoso = false;
        $totalCombinaciones = count(self::ENDPOINTS) * count(self::SOAP_VARIATIONS);
        $combo = 0;

        foreach (self::ENDPOINTS as $endpointLabel => $endpointUrl) {
            foreach (self::SOAP_VARIATIONS as $variacion) {
                $combo++;
                $label = "[{$combo}/{$totalCombinaciones}] {$endpointLabel} + {$variacion['label']}";
                
                $this->line("<fg=gray>─── {$label} ───</>");

                $result = $this->probarCombinacion(
                    $endpointUrl, 
                    $rfc, 
                    $claveWs, 
                    $operacion, 
                    $variacion
                );

                $resultados[] = [
                    'endpoint' => $endpointLabel,
                    'variacion' => $variacion['label'],
                    'http_code' => $result['http_code'],
                    'resultado' => $result['tipo'],
                    'detalle' => $result['detalle'] ?? '',
                ];

                if ($result['tipo'] === 'EXITO') {
                    $exitoso = true;
                    $this->info("  >>> ¡ÉXITO! eDocument encontrado: {$result['eDocument']}");
                    break 2; // Salir de ambos loops
                } elseif ($result['tipo'] === 'PROCESANDO') {
                    $this->warn("  >>> VUCEM respondió OK pero aún procesando");
                } elseif ($result['tipo'] === 'DISPATCH_ERROR') {
                    $this->line("  <fg=red>✗</> Dispatch method not found (elemento incorrecto)");
                } elseif ($result['tipo'] === 'CONEXION_ERROR') {
                    $this->line("  <fg=red>✗</> Error de conexión: {$result['detalle']}");
                } elseif ($result['tipo'] === 'ERROR_NEGOCIO') {
                    $this->warn("  ⚠ Error de negocio: {$result['detalle']}");
                } else {
                    $this->line("  <fg=yellow>?</> HTTP {$result['http_code']} - {$result['detalle']}");
                }
            }
        }

        // ─── Paso 3: Resumen ───
        $this->newLine();
        $this->info('══ RESUMEN DE RESULTADOS ══');
        
        $headers = ['Endpoint', 'Variación SOAP', 'HTTP', 'Resultado'];
        $rows = [];
        foreach ($resultados as $r) {
            $rows[] = [
                substr($r['endpoint'], 0, 30),
                substr($r['variacion'], 0, 40),
                $r['http_code'],
                $r['resultado'],
            ];
        }
        $this->table($headers, $rows);

        if (!$exitoso) {
            $this->newLine();
            $this->warn('Ninguna combinación obtuvo el eDocument directamente.');
            $this->line('Posibles causas:');
            $this->line('  1. VUCEM aún está procesando la operación (re-intentar más tarde)');
            $this->line('  2. El servicio de consulta tiene un endpoint/WSDL diferente al esperado');
            $this->line('  3. Ejecutar con --fetch-wsdl para intentar descargar el WSDL real');
        }

        return self::SUCCESS;
    }

    /**
     * Probar una combinación específica de endpoint + variación SOAP
     */
    private function probarCombinacion(
        string $endpointUrl, 
        string $rfc, 
        string $claveWs, 
        string $operacion, 
        array $variacion
    ): array {
        $created = gmdate("Y-m-d\TH:i:s\Z");
        $expires = gmdate("Y-m-d\TH:i:s\Z", strtotime('+5 minutes'));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:dig="' . $variacion['ns'] . '">
   <soapenv:Header>
      <wsse:Security soapenv:mustUnderstand="1" 
                     xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                     xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
         <wsu:Timestamp wsu:Id="TS-1">
            <wsu:Created>' . $created . '</wsu:Created>
            <wsu:Expires>' . $expires . '</wsu:Expires>
         </wsu:Timestamp>
         <wsse:UsernameToken wsu:Id="UsernameToken-1">
            <wsse:Username>' . htmlspecialchars($rfc, ENT_XML1) . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($claveWs, ENT_XML1) . '</wsse:Password>
         </wsse:UsernameToken>
      </wsse:Security>
   </soapenv:Header>
   <soapenv:Body>
      <dig:' . $variacion['elemento'] . '>
         <dig:numeroOperacion>' . $operacion . '</dig:numeroOperacion>
      </dig:' . $variacion['elemento'] . '>
   </soapenv:Body>
</soapenv:Envelope>';

        try {
            $ch = curl_init($endpointUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'SOAPAction: "' . ($variacion['soap_action'] ?: '') . '"',
                    'Content-Length: ' . strlen($xml),
                ],
            ]);

            $body = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'tipo' => 'CONEXION_ERROR',
                    'http_code' => 0,
                    'detalle' => "[{$errno}] {$error}",
                ];
            }

            // Extraer XML de MTOM si es necesario
            $xmlBody = $body;
            if (preg_match('/<\?xml.*?\?>.+<\/S:Envelope>/s', $body, $match)) {
                $xmlBody = $match[0];
            } elseif (preg_match('/<S:Envelope.*?<\/S:Envelope>/s', $body, $match)) {
                $xmlBody = $match[0];
            }

            // SOAP Fault - dispatch method not found
            if ($httpCode === 500 && str_contains($xmlBody, 'Cannot find dispatch method')) {
                return [
                    'tipo' => 'DISPATCH_ERROR',
                    'http_code' => $httpCode,
                    'detalle' => 'Cannot find dispatch method',
                ];
            }

            // SOAP Fault genérico
            if (preg_match('/<[:\w]*faultstring>(.*?)<\/[:\w]*faultstring>/s', $xmlBody, $fMatch)) {
                return [
                    'tipo' => 'SOAP_FAULT',
                    'http_code' => $httpCode,
                    'detalle' => trim($fMatch[1]),
                ];
            }

            // Verificar errores de negocio
            if (preg_match('/<[:\w]*tieneError>(.*?)<\/[:\w]*tieneError>/', $xmlBody, $matchErr)) {
                if (filter_var($matchErr[1], FILTER_VALIDATE_BOOLEAN)) {
                    $msg = '';
                    if (preg_match('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/', $xmlBody, $mMsg)) {
                        $msg = trim($mMsg[1]);
                    }
                    return [
                        'tipo' => 'ERROR_NEGOCIO',
                        'http_code' => $httpCode,
                        'detalle' => $msg ?: 'Error sin mensaje',
                    ];
                }
            }

            // Buscar eDocument en cualquier formato
            foreach ([
                '/<[:\w]*eDocument>(.*?)<\/[:\w]*eDocument>/s',
                '/<[:\w]*numeroEdocument>(.*?)<\/[:\w]*numeroEdocument>/s',
                '/<[:\w]*numeroEDocument>(.*?)<\/[:\w]*numeroEDocument>/s',
                '/<[:\w]*edocument>(.*?)<\/[:\w]*edocument>/s',
                '/<[:\w]*folioEdocument>(.*?)<\/[:\w]*folioEdocument>/s',
            ] as $regex) {
                if (preg_match($regex, $xmlBody, $edocMatch)) {
                    Log::info('[TEST_CONSULTA_OP] ¡eDocument encontrado!', [
                        'operacion' => $this->argument('operacion'),
                        'eDocument' => $edocMatch[1],
                        'endpoint' => $endpointUrl,
                        'variacion' => $variacion['label'],
                    ]);
                    return [
                        'tipo' => 'EXITO',
                        'http_code' => $httpCode,
                        'eDocument' => $edocMatch[1],
                        'detalle' => 'eDocument: ' . $edocMatch[1],
                    ];
                }
            }

            // Buscar si está procesando
            if (preg_match('/<[:\w]*mensaje>(.*?)<\/[:\w]*mensaje>/s', $xmlBody, $mMsg)) {
                $msg = trim($mMsg[1]);
                if (str_contains(strtolower($msg), 'procesando')) {
                    return [
                        'tipo' => 'PROCESANDO',
                        'http_code' => $httpCode,
                        'detalle' => $msg,
                    ];
                }
            }

            // Si HTTP 200 pero no se encontró nada conocido: log la respuesta para análisis
            if ($httpCode === 200) {
                Log::info('[TEST_CONSULTA_OP] Respuesta 200 sin eDocument reconocido', [
                    'operacion' => $this->argument('operacion'),
                    'endpoint' => $endpointUrl,
                    'variacion' => $variacion['label'],
                    'body' => substr($xmlBody, 0, 3000),
                ]);
                return [
                    'tipo' => 'RESPUESTA_DESCONOCIDA',
                    'http_code' => $httpCode,
                    'detalle' => 'HTTP 200 pero estructura no reconocida. Ver logs.',
                    'body' => substr($xmlBody, 0, 500),
                ];
            }

            return [
                'tipo' => 'OTRO',
                'http_code' => $httpCode,
                'detalle' => 'HTTP ' . $httpCode . ' - ' . substr($xmlBody, 0, 200),
            ];

        } catch (Exception $e) {
            return [
                'tipo' => 'EXCEPCION',
                'http_code' => 0,
                'detalle' => $e->getMessage(),
            ];
        }
    }

    /**
     * Intentar descargar WSDLs de los endpoints candidatos
     */
    private function fetchWsdls(): void
    {
        $wsdlUrls = [
            'ConsultarEDocument' => 'https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEDocumentDigitalizarDocumentoService?wsdl',
            'ConsultarEDocument_443' => 'https://www.ventanillaunica.gob.mx:443/ventanilla/ConsultarEDocumentDigitalizarDocumentoService?wsdl',
            'ConsultarEDocument_HA' => 'https://www.ventanillaunica.gob.mx/ventanilla-ws-HA/ConsultarEDocumentDigitalizarDocumentoService?wsdl',
            'Digitalizar' => 'https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService?wsdl',
        ];

        foreach ($wsdlUrls as $label => $url) {
            $this->line("  Probando WSDL: <fg=cyan>{$label}</>");
            $this->line("  URL: <fg=gray>{$url}</>");

            try {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                ]);
                $body = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    $this->line("  <fg=red>✗ Error: {$error}</>");
                    $this->newLine();
                    continue;
                }

                $this->line("  HTTP: <fg=" . ($httpCode === 200 ? 'green' : 'red') . ">{$httpCode}</>");

                if ($httpCode === 200 && str_contains($body, 'definitions')) {
                    $this->info("  ✓ WSDL VÁLIDO encontrado!");
                    
                    // Extraer operaciones del WSDL
                    if (preg_match_all('/operation name="([^"]+)"/', $body, $ops)) {
                        $uniqueOps = array_unique($ops[1]);
                        $this->line("  Operaciones: <fg=yellow>" . implode(', ', $uniqueOps) . "</>");
                    }
                    
                    // Extraer namespaces targetNamespace
                    if (preg_match('/targetNamespace="([^"]+)"/', $body, $nsMatch)) {
                        $this->line("  Target NS: <fg=yellow>{$nsMatch[1]}</>");
                    }

                    // Extraer elementos de petición
                    if (preg_match_all('/element[^>]*name="([^"]*[Rr]equest[^"]*|[^"]*[Pp]eticion[^"]*)"/', $body, $elems)) {
                        $this->line("  Elementos petición: <fg=yellow>" . implode(', ', array_unique($elems[1])) . "</>");
                    }

                    // Guardar WSDL localmente para análisis
                    $savePath = storage_path("app/wsdl_{$label}.xml");
                    file_put_contents($savePath, $body);
                    $this->line("  Guardado en: <fg=gray>{$savePath}</>");
                } else {
                    $this->line("  <fg=red>✗ No es WSDL válido (HTTP {$httpCode})</>");
                    if ($httpCode === 200) {
                        $this->line("  Preview: <fg=gray>" . substr(strip_tags($body), 0, 150) . "</>");
                    }
                }
            } catch (Exception $e) {
                $this->line("  <fg=red>✗ Excepción: {$e->getMessage()}</>");
            }
            $this->newLine();
        }
    }
}
