<?php

namespace App\Console\Commands;

use App\Models\VucemErrorLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LimpiarLogsAntiguos extends Command
{
    protected $signature   = 'logs:limpiar {--dias=7 : Días de historial a conservar}';
    protected $description = 'Elimina registros de vucem_error_logs y archivos de log antiguos';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        // ── 1. Limpiar tabla vucem_error_logs ─────────────────────────────────
        $eliminados = VucemErrorLog::where('created_at', '<', now()->subDays($dias))->delete();
        $this->info("vucem_error_logs: {$eliminados} registros eliminados (anteriores a {$dias} días).");

        // ── 2. Limpiar archivos .log del disco más allá de los días configurados ─
        // El canal 'daily' de Monolog ya rota automáticamente según LOG_DAILY_DAYS,
        // pero otros archivos (debug, tests) pueden acumularse.
        $logDir   = storage_path('logs');
        $archivos = glob($logDir . '/*.log') ?: [];
        $limite   = now()->subDays($dias)->startOfDay();
        $borrados = 0;

        foreach ($archivos as $archivo) {
            // Saltar archivos que no tienen fecha en el nombre (ej. laravel.log sin rotar)
            if (!preg_match('/\d{4}-\d{2}-\d{2}/', basename($archivo), $m)) {
                continue;
            }
            $fechaArchivo = \Carbon\Carbon::createFromFormat('Y-m-d', $m[0])->startOfDay();
            if ($fechaArchivo->lt($limite)) {
                @unlink($archivo);
                $borrados++;
            }
        }
        $this->info("Archivos de log: {$borrados} archivos eliminados.");

        Log::info('[MANTENIMIENTO] Limpieza de logs completada', [
            'vucem_error_logs_eliminados' => $eliminados,
            'archivos_log_eliminados'     => $borrados,
            'dias_conservados'            => $dias,
        ]);

        return self::SUCCESS;
    }
}
