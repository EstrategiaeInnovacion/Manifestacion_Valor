<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VucemConvertKey extends Command
{
    protected $signature = 'vucem:convert-key {--in=} {--out=} {--pass=}';
    protected $description = 'Convierte una llave privada DER (.key) a PEM y valida que se carga con openssl + contraseña.';

    public function handle(): int
    {
        $in = (string) $this->normalizePath($this->option('in'));
        $out = (string) $this->normalizePath($this->option('out'));
        $pass = (string) ($this->option('pass') ?? '');

        if ($in === '' || !file_exists($in)) {
            $this->error('Debe proporcionar --in con ruta válida a .key');
            return self::FAILURE;
        }
        if ($out === '') {
            $out = preg_replace('/\.(key|der)$/i', '.pem', $in) ?? ($in . '.pem');
        }

        $this->line('Entrada: ' . $in);
        $this->line('Salida: ' . $out);

        $content = @file_get_contents($in);
        if ($content === false) {
            $this->error('No se pudo leer el archivo de entrada');
            return self::FAILURE;
        }

        // Intentar conversión básica (PKCS8)
        $pem1 = "-----BEGIN PRIVATE KEY-----\n" . chunk_split(base64_encode($content), 64, "\n") . "-----END PRIVATE KEY-----\n";
        $pem2 = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split(base64_encode($content), 64, "\n") . "-----END RSA PRIVATE KEY-----\n";

        $ok = false; $chosen = null;
        $test1 = @openssl_pkey_get_private($pem1, $pass);
        if ($test1 !== false) {
            openssl_free_key($test1);
            $ok = true; $chosen = $pem1;
            $this->info('Conversión válida como PRIVATE KEY');
        } else {
            $test2 = @openssl_pkey_get_private($pem2, $pass);
            if ($test2 !== false) {
                openssl_free_key($test2);
                $ok = true; $chosen = $pem2;
                $this->info('Conversión válida como RSA PRIVATE KEY');
            }
        }

        if (!$ok || $chosen === null) {
            $this->error('No se pudo cargar la llave con la contraseña proporcionada tras la conversión.');
            $this->line('Sugerencia: verifique la contraseña o convierta con OpenSSL externo a PKCS#8 PEM.');
            return self::FAILURE;
        }

        if (@file_put_contents($out, $chosen) === false) {
            $this->error('No se pudo escribir el archivo de salida');
            return self::FAILURE;
        }

        $this->info('Archivo PEM generado: ' . $out);
        return self::SUCCESS;
    }

    private function normalizePath(?string $path): string
    {
        $p = (string) ($path ?? '');
        $p = trim($p, "\"' ");
        if (DIRECTORY_SEPARATOR === '\\') {
            $p = str_replace('\\\\', '\\', $p);
        }
        return $p;
    }
}
