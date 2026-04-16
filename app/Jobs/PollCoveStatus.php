<?php

namespace App\Jobs;

use App\Models\MvClientApplicant;
use App\Models\CoveDocument;
use App\Services\ConsultarRespuestaCoveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollCoveStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $coveDocumentId;
    protected $applicantId;
    protected $numeroOperacion;
    protected $claveWebservice;
    protected $attemptNumber;

    // Configuración de reintentos
    public $tries = 10;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $coveDocumentId,
        $applicantId,
        $numeroOperacion,
        $claveWebservice,
        $attemptNumber = 1
    ) {
        $this->coveDocumentId = $coveDocumentId;
        $this->applicantId = $applicantId;
        $this->numeroOperacion = $numeroOperacion;
        $this->claveWebservice = $claveWebservice;
        $this->attemptNumber = $attemptNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(ConsultarRespuestaCoveService $consultarService): void
    {
        $coveDocument = CoveDocument::find($this->coveDocumentId);
        $applicant = MvClientApplicant::find($this->applicantId);

        if (!$coveDocument || !$applicant) {
            Log::error('[COVE_POLL] Faltan modelos para polling', [
                'cove_id' => $this->coveDocumentId,
                'applicant_id' => $this->applicantId
            ]);
            return;
        }

        // Evitamos bucles infinitos agresivos si llega al límite
        if ($this->attemptNumber >= 15) {
            Log::warning('[COVE_POLL] Límite de intentos alcanzado para operación: ' . $this->numeroOperacion);
            $coveDocument->status = 'error';
            $coveDocument->save();
            return;
        }

        try {
            Log::info('[COVE_POLL] Consultando operación', [
                'cove_id' => $this->coveDocumentId,
                'numero_operacion' => $this->numeroOperacion,
                'intento' => $this->attemptNumber
            ]);

            $response = $consultarService->consultarPorNumeroOperacion(
                $this->numeroOperacion,
                strtoupper($applicant->applicant_rfc),
                $this->claveWebservice
            );

            if ($response['status'] === 'EXITOSO' && !empty($response['eDocument'])) {
                Log::info('[COVE_POLL] eDocument recuperado con éxito!', ['eDocument' => $response['eDocument']]);
                
                $coveDocument->e_document = $response['eDocument'];
                $coveDocument->status = 'enviado';
                $coveDocument->xml_respuesta = $response['raw_response'] ?? null;
                $coveDocument->save();
                return;

            } elseif ($response['status'] === 'ERROR') {
                Log::error('[COVE_POLL] Error de VUCEM detectado', ['errores' => $response['errores']]);
                $coveDocument->status = 'rechazado';
                $coveDocument->xml_respuesta = $response['raw_response'] ?? null;
                $coveDocument->save();
                return;

            } else {
                // PENDIENTE
                Log::info('[COVE_POLL] Todavía procesando, re-encolando...', ['intento' => $this->attemptNumber]);
                
                PollCoveStatus::dispatch(
                    $this->coveDocumentId,
                    $this->applicantId,
                    $this->numeroOperacion,
                    $this->claveWebservice,
                    $this->attemptNumber + 1
                )->delay(now()->addSeconds(60));
            }

        } catch (\Exception $e) {
            Log::error('[COVE_POLL] Excepción: ' . $e->getMessage());
            // En caso de excepción de red, reintentamos igual si no superamos límite
            self::dispatch(
                $this->coveDocumentId,
                $this->applicantId,
                $this->numeroOperacion,
                $this->claveWebservice,
                $this->attemptNumber + 1
            )->delay(now()->addSeconds(60));
        }
    }
}
