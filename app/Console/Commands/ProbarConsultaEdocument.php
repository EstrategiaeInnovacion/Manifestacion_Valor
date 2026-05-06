<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MvClientApplicant;
use App\Services\ConsultarEdocumentService;
use App\Services\EFirmaService;
use Exception;

class ProbarConsultaEdocument extends Command
{
    protected $signature = 'vucem:probar-edocument
                            {folio       : Folio eDocument (ej: COVE2681QNZY3)}
                            {applicant   : ID del solicitante en BD}
                            {--ws=       : Clave WebService a probar (sobreescribe la guardada en BD)}';

    protected $description = 'Prueba la consulta de eDocument en VUCEM usando el mismo flujo que la app.';

    public function handle(): int
    {
        $folio       = trim($this->argument('folio'));
        $applicantId = (int) $this->argument('applicant');
        $wsOverride  = trim((string) $this->option('ws'));

        $applicant = MvClientApplicant::find($applicantId);
        if (!$applicant) {
            $this->error("Solicitante ID {$applicantId} no encontrado.");
            return self::FAILURE;
        }

        $this->line("Solicitante : <fg=cyan>{$applicant->business_name}</>");
        $this->line("RFC         : <fg=cyan>{$applicant->applicant_rfc}</>");
        $this->line("Folio       : <fg=yellow>{$folio}</>");

        // Credenciales
        if (!$applicant->hasVucemCredentials()) {
            $this->error('El solicitante no tiene credenciales VUCEM almacenadas.');
            return self::FAILURE;
        }

        $claveWs = $wsOverride ?: $applicant->vucem_webservice_key;

        if (empty($claveWs)) {
            $this->error('No hay clave WebService disponible (ni en BD ni por --ws).');
            return self::FAILURE;
        }

        $preview = substr($claveWs, 0, 4) . str_repeat('*', max(0, strlen($claveWs) - 4));
        $this->line("Clave WS    : <fg=white>{$preview}</> (" . strlen($claveWs) . " chars)");
        $this->line($wsOverride ? '  <fg=yellow>↑ usando clave proporcionada por --ws</>' : '  <fg=gray>↑ usando clave almacenada en BD</>');
        $this->newLine();

        // Desencriptar y escribir archivos temporales
        $certBin = base64_decode($applicant->vucem_cert_file, true);
        $keyBin  = base64_decode($applicant->vucem_key_file, true);
        $pass    = $applicant->vucem_password;

        if (!$certBin || !$keyBin || !$pass) {
            $this->error('Credenciales incompletas en BD (cert/key/password).');
            return self::FAILURE;
        }

        $tmpCert = tempnam(sys_get_temp_dir(), 'cert_');
        $tmpKey  = tempnam(sys_get_temp_dir(), 'key_');

        try {
            file_put_contents($tmpCert, $certBin);
            file_put_contents($tmpKey,  $keyBin);

            $this->line('Llamando a VUCEM ConsultarEdocument...');
            $this->line('<fg=gray>Esto puede tardar 5-15 segundos.</>');
            $this->newLine();

            $service = app(ConsultarEdocumentService::class);

            $result = $service->consultarEdocument(
                $folio,
                $applicant->applicant_rfc,
                $claveWs,
                $tmpCert,
                $tmpKey,
                $pass
            );

            if ($result['success']) {
                $this->info('✓  Consulta EXITOSA');
                $this->line('Mensaje: ' . $result['message']);

                if (!empty($result['cove_data'])) {
                    $this->line('Datos COVE recibidos: SÍ');
                }
                if (!empty($result['archivos'])) {
                    $this->line('Archivos adjuntos   : ' . count($result['archivos']));
                }
            } else {
                $this->error('✗  Consulta FALLIDA');
                $this->line('Mensaje: ' . ($result['message'] ?? 'Sin mensaje'));
            }

        } finally {
            @unlink($tmpCert);
            @unlink($tmpKey);
        }

        return self::SUCCESS;
    }
}
