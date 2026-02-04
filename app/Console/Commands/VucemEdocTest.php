<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VucemEDocumentService;

class VucemEdocTest extends Command
{
    protected $signature = 'vucem:edoc-test {folio} {--rfc=} {--cer=} {--key=} {--pass=} {--wsse-user=} {--wsse-pass=} {--token=TEXT}';
    protected $description = 'Diagnóstico del servicio VUCEM ConsultarEdocumentService (WSDL, conexión 443 y llamada real).';

    public function handle(): int
    {
        $folio = (string) $this->argument('folio');
        $endpoint = (string) config('edocument.endpoint');
        $wsdlUrl = (string) config('edocument.wsdl_url');
        $soapAction = (string) config('edocument.soap_action');
        $timeout = (int) config('edocument.timeout');
        $debug = (bool) config('edocument.debug');

        $this->info('--- VUCEM eDocument Diagnóstico ---');
        $this->line('Endpoint: ' . $endpoint);
        $this->line('WSDL URL: ' . $wsdlUrl);
        $this->line('SOAPAction: ' . $soapAction);

        // 1) GET al WSDL real y mostrar HTTP code
        $httpCode = $this->probeHttpGet($wsdlUrl, $timeout);
        $this->line('WSDL GET HTTP Code: ' . $httpCode);

        // 2) Probar conexión 443 con fsockopen
        $host = 'www.ventanillaunica.gob.mx';
        $ok443 = $this->probeSocket443($host, 443, $timeout);
        $this->line('Conexión 443 fsockopen: ' . ($ok443 ? 'OK' : 'FAIL'));

        // 3) Llamada real a ConsultarEdocument (cURL + WSSE firmado)
        $this->line('Ejecutando consulta real...');
        $service = new VucemEDocumentService();

        $rfc = (string) ($this->option('rfc') ?: env('VUCEM_WSSE_USERNAME', 'TESTRFC'));
        $cer = (string) ($this->option('cer') ?: env('VUCEM_CSD_CER_PATH'));
        $key = (string) ($this->option('key') ?: env('VUCEM_CSD_KEY_PATH'));
        $pass = (string) ($this->option('pass') ?: env('VUCEM_CSD_KEY_PASS'));

        // Normalize Windows quoted paths and slashes
        $cer = $this->normalizePath($cer);
        $key = $this->normalizePath($key);

        $this->line('Ruta CER: ' . $cer . ' (exists=' . (file_exists($cer) ? 'yes' : 'no') . ')');
        $this->line('Ruta KEY: ' . $key . ' (exists=' . (file_exists($key) ? 'yes' : 'no') . ')');

        if (!is_file($cer) || !is_file($key)) {
            $this->error('Rutas de CSD inválidas. Use --cer y --key o configure variables de entorno.');
            return self::FAILURE;
        }

        // Optional WSSE credentials override via CLI
        if ($this->option('wsse-user')) {
            putenv('VUCEM_EDOC_USERNAME=' . (string) $this->option('wsse-user'));
        } elseif ($rfc) {
            // Default to RFC as Username if provided
            putenv('VUCEM_EDOC_USERNAME=' . $rfc);
        }
        if ($this->option('wsse-pass')) {
            putenv('VUCEM_EDOC_PASSWORD=' . (string) $this->option('wsse-pass'));
        }
        if ($this->option('token')) {
            putenv('VUCEM_EDOC_USER_TOKEN_MODE=' . strtoupper((string) $this->option('token')));
        }

        $result = $service->consultarEdocument($folio, $cer, $key, $pass, $rfc);

        if ($debug) {
            $debugLog = storage_path('logs/vucem_edoc_debug.log');
            $this->line('Debug log: ' . $debugLog);
        }

        if ($result['ok']) {
            $this->info('Consulta OK');
            $this->line('exists: ' . ($result['exists'] ? 'true' : 'false'));
            $this->line('status: ' . ($result['status'] ?? 'null'));
            $this->line('message: ' . ($result['message'] ?? ''));
        } else {
            $this->error('Consulta FAIL');
            $this->line('message: ' . ($result['message'] ?? ''));
        }

        return self::SUCCESS;
    }

    private function probeHttpGet(string $url, int $timeout): int
    {
        $code = 0;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => ['method' => 'GET', 'timeout' => $timeout],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $fp = @fopen($url, 'r', false, $ctx);
            if ($fp) {
                $meta = stream_get_meta_data($fp);
                foreach (($meta['wrapper_data'] ?? []) as $h) {
                    if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $h, $m)) {
                        $code = (int) ($m[1] ?? 0);
                        break;
                    }
                }
                fclose($fp);
            }
        }
        return $code;
    }

    private function probeSocket443(string $host, int $port, int $timeout): bool
    {
        $errno = 0; $errstr = '';
        $conn = @fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);
        if (is_resource($conn)) {
            fclose($conn);
            return true;
        }
        return false;
    }

    private function normalizePath(?string $path): string
    {
        $p = (string) ($path ?? '');
        // Trim quotes
        $p = trim($p, "\"' ");
        // Replace backslashes with proper directory separators
        if (DIRECTORY_SEPARATOR === '\\') {
            $p = str_replace('\\\\', '\\', $p);
        }
        return $p;
    }
}
