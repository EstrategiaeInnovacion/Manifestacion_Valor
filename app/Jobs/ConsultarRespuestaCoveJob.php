<?php

namespace App\Jobs;

use App\Models\Cove;
use App\Services\ConsultarRespuestaCoveService;
use App\Services\EFirmaService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * ConsultarRespuestaCoveJob
 *
 * Job que se ejecuta cada 5 minutos via Scheduler para buscar
 * el e-Document de todos los COVEs que ya fueron enviados a VUCEM
 * y que tienen un numero_operacion asignado.
 *
 * NOTA: Este job NO implementa ShouldQueue para evitar depender del worker de cola.
 * Se ejecuta síncronamente dentro del proceso del scheduler.
 */
class ConsultarRespuestaCoveJob
{
    use Dispatchable;

    public function handle(EFirmaService $efirmaService): void
    {
        $servicio = new ConsultarRespuestaCoveService($efirmaService);

        // Buscar COVEs enviados que tienen numero_operacion y aún no procesados
        $coves = Cove::where('status', 'enviado')
            ->whereNotNull('numero_operacion')
            ->where('intentos_consulta', '<', 24)
            ->get();

        if ($coves->isEmpty()) {
            Log::info('[ConsultarRespuestaCoveJob] Sin COVEs pendientes de consulta.');
            return;
        }

        Log::info('[ConsultarRespuestaCoveJob] Iniciando polling de e-Documents', [
            'total' => $coves->count(),
        ]);

        foreach ($coves as $cove) {
            try {
                // Incrementar el contador de intentos
                $cove->increment('intentos_consulta');

                $resultado = $servicio->consultarRespuesta($cove);

                if ($resultado['success']) {
                    Log::info('[ConsultarRespuestaCoveJob] ✅ e-Document obtenido', [
                        'cove_id'   => $cove->id,
                        'edocument' => $resultado['edocument'] ?? 'N/A',
                    ]);
                } elseif (!($resultado['pending'] ?? true)) {
                    // Error definitivo (VUCEM rechazó el COVE)
                    Log::error('[ConsultarRespuestaCoveJob] ❌ COVE rechazado por VUCEM', [
                        'cove_id' => $cove->id,
                        'mensaje' => $resultado['message'] ?? '',
                    ]);
                } else {
                    // Aún pendiente, se reintentará la próxima vez
                    Log::info('[ConsultarRespuestaCoveJob] ⏳ COVE aún pendiente en VUCEM', [
                        'cove_id'  => $cove->id,
                        'intento'  => $cove->intentos_consulta,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[ConsultarRespuestaCoveJob] Excepción consultando COVE', [
                    'cove_id' => $cove->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
