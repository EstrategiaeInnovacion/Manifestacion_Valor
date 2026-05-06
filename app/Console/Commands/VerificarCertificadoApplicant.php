<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MvClientApplicant;
use PhpCfdi\Credentials\Certificate;
use Exception;

class VerificarCertificadoApplicant extends Command
{
    protected $signature = 'vucem:verificar-cert {applicant_id : ID del solicitante en BD}';

    protected $description = 'Lee el certificado almacenado en BD para un solicitante y muestra el RFC que contiene.';

    public function handle(): int
    {
        $id = (int) $this->argument('applicant_id');

        $applicant = MvClientApplicant::find($id);

        if (!$applicant) {
            $this->error("No se encontró el solicitante con ID: {$id}");
            return self::FAILURE;
        }

        $this->line("Solicitante : <fg=cyan>{$applicant->business_name}</>");
        $this->line("RFC en BD   : <fg=cyan>{$applicant->applicant_rfc}</>");
        $this->newLine();

        // --- Certificado ---
        if (empty($applicant->vucem_cert_file)) {
            $this->warn('vucem_cert_file : VACÍO — no hay certificado guardado.');
        } else {
            $certBin = base64_decode($applicant->vucem_cert_file, true);

            if ($certBin === false || strlen($certBin) < 100) {
                $this->error('vucem_cert_file : El contenido no es base64 válido o está corrupto.');
            } else {
                $this->line('<fg=green>vucem_cert_file : presente (' . strlen($certBin) . ' bytes)</>');

                // Escribir en archivo temporal y leer con PhpCfdi
                $tmpCert = tempnam(sys_get_temp_dir(), 'cert_check_');
                file_put_contents($tmpCert, $certBin);

                try {
                    $cert = Certificate::openFile($tmpCert);
                    $rfcCert  = $cert->rfc();
                    $vigencia = $cert->validTo();           // returns string
                    $vigDt    = $cert->validToDateTime();
                    $sujeto   = $cert->legalName();
                    $serial   = $cert->serialNumber()->hexadecimal();

                    $rfcMatch = ($rfcCert === $applicant->applicant_rfc);

                    $vigColor = ($vigDt > new \DateTimeImmutable()) ? 'green' : 'red';

                    $this->line("RFC en certificado : <fg=" . ($rfcMatch ? 'green' : 'red') . ">{$rfcCert}</>");
                    $this->line("Nombre en cert     : <fg=white>{$sujeto}</>");
                    $this->line("Número de serie    : <fg=white>{$serial}</>");
                    $this->line("Vigencia hasta     : <fg={$vigColor}>{$vigencia}</>");
                    $this->newLine();

                    if (!$rfcMatch) {
                        $this->error("⚠  El certificado almacenado pertenece a RFC [{$rfcCert}], NO a [{$applicant->applicant_rfc}].");
                        $this->line("   Este mismatch es la causa del SOAP Fault env:Server en VUCEM.");
                    } else {
                        $this->info("✓  El certificado corresponde al RFC correcto [{$rfcCert}].");
                    }
                } catch (Exception $e) {
                    $this->error('No se pudo parsear el certificado: ' . $e->getMessage());
                } finally {
                    @unlink($tmpCert);
                }
            }
        }

        $this->newLine();

        // --- Llave privada (solo verifica presencia y tamaño) ---
        if (empty($applicant->vucem_key_file)) {
            $this->warn('vucem_key_file  : VACÍO — no hay llave privada guardada.');
        } else {
            $keyBin = base64_decode($applicant->vucem_key_file, true);
            $this->line('<fg=green>vucem_key_file  : presente (' . strlen($keyBin) . ' bytes)</>');
        }

        // --- Contraseña ---
        $this->line('vucem_password  : ' . (empty($applicant->vucem_password) ? '<fg=red>VACÍA</>' : '<fg=green>presente</>'));

        // --- Clave WS ---
        if (empty($applicant->vucem_webservice_key)) {
            $this->warn('vucem_ws_key    : VACÍA — sin clave de WebService.');
        } else {
            $wsKey = $applicant->vucem_webservice_key;
            $preview = substr($wsKey, 0, 4) . str_repeat('*', max(0, strlen($wsKey) - 4));
            $this->line("vucem_ws_key    : <fg=green>{$preview}</> (" . strlen($wsKey) . " chars)");
        }

        return self::SUCCESS;
    }
}
