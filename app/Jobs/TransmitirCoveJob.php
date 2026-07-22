<?php

namespace App\Jobs;

use App\Models\Cove;
use App\Services\CoveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TransmitirCoveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $coveId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(int $coveId)
    {
        $this->coveId = $coveId;
    }

    /**
     * Execute the job.
     */
    public function handle(CoveService $coveService): void
    {
        $cove = Cove::find($this->coveId);

        if (!$cove) {
            Log::error("[TransmitirCoveJob] COVE no encontrado", ['cove_id' => $this->coveId]);
            return;
        }

        // Si ya está procesado o en error, ignorar (evitar reintentos innecesarios)
        if (in_array($cove->status, ['procesado', 'error'])) {
            Log::info("[TransmitirCoveJob] COVE ya procesado o en error, saltando", [
                'cove_id' => $this->coveId,
                'status' => $cove->status,
                'edocument' => $cove->edocument
            ]);
            return;
        }

        Log::info("[TransmitirCoveJob] Procesando transmisión de COVE", ['cove_id' => $cove->id]);

        $cove->update(['status' => 'enviado']);

        $result = $coveService->transmitirCove($cove);

        if ($result['success']) {
            Log::info("[TransmitirCoveJob] COVE transmitido exitosamente", [
                'cove_id' => $cove->id,
                'edocument' => $result['edocument'] ?? 'PENDIENTE'
            ]);
        } else {
            Log::warning("[TransmitirCoveJob] Error al transmitir COVE. Se reintentará si quedan intentos.", [
                'cove_id' => $cove->id,
                'error' => $result['message']
            ]);

            throw new \Exception("Transmisión de COVE fallida: " . $result['message']);
        }
    }
}
