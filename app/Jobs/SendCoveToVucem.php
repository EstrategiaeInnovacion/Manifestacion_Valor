<?php

namespace App\Jobs;

use App\Models\MvClientApplicant;
use App\Models\CoveDocument;
use App\Services\CoveVucemSoapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCoveToVucem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $coveDocumentId;
    protected $applicantId;
    protected $xmlIso;
    protected $claveWebservice;

    /**
     * Create a new job instance.
     */
    public function __construct($coveDocumentId, $applicantId, $xmlIso, $claveWebservice)
    {
        $this->coveDocumentId = $coveDocumentId;
        $this->applicantId = $applicantId;
        $this->xmlIso = $xmlIso;
        $this->claveWebservice = $claveWebservice;
    }

    /**
     * Execute the job.
     */
    public function handle(CoveVucemSoapService $coveVucemSoapService): void
    {
        $coveDocument = CoveDocument::find($this->coveDocumentId);
        $applicant = MvClientApplicant::find($this->applicantId);

        if (!$coveDocument || !$applicant) {
            Log::error('[COVE_JOB] Faltan modelos para el envío de COVE', [
                'cove_id' => $this->coveDocumentId,
                'applicant_id' => $this->applicantId
            ]);
            return;
        }

        try {
            Log::info('[COVE_JOB] Iniciando envío de COVE a VUCEM', ['id' => $this->coveDocumentId]);

            $response = $coveVucemSoapService->sendToVucem(
                $this->xmlIso,
                strtoupper($applicant->applicant_rfc),
                $this->claveWebservice,
                false // Modo test = false, se configura desde endpoint
            );

            if ($response['success'] && !empty($response['numero_operacion'])) {
                // Actualizar a estado intermedio de procesamiento
                $coveDocument->status = 'procesando_vucem';
                $coveDocument->numero_operacion = $response['numero_operacion'];
                $coveDocument->xml_enviado = $this->xmlIso;
                $coveDocument->save();
                
                Log::info('[COVE_JOB] Envío exitoso, lanzando polling', ['numero_operacion' => $response['numero_operacion']]);

                // Llamar al job de Polling con retraso (ej. 60s)
                PollCoveStatus::dispatch(
                    $this->coveDocumentId,
                    $this->applicantId,
                    $response['numero_operacion'],
                    $this->claveWebservice,
                    1 // Intento inicial
                )->delay(now()->addSeconds(60));

            } else {
                $coveDocument->status = 'error';
                $coveDocument->xml_enviado = $this->xmlIso;
                $coveDocument->save();
                Log::error('[COVE_JOB] El envío falló', ['response' => $response]);
            }

        } catch (\Exception $e) {
            Log::error('[COVE_JOB] Excepción en envío: ' . $e->getMessage());
            $coveDocument->status = 'error';
            $coveDocument->save();
        }
    }
}
