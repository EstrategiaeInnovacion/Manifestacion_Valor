<?php

namespace App\Console\Commands;

use App\Models\Cove;
use Illuminate\Console\Command;

class ForceCoveEdoc extends Command
{
    protected $signature = 'coves:force-edoc
                            {cove? : ID del COVE a procesar (opcional, si se omite procesa todos los atorados)}
                            {--revert : Revierte un COVE procesado de vuelta a estado enviado (para reintentar)}';

    protected $description = 'Fuerza/simula e-Document para COVEs atorados, o revierte COVEs a enviado para reintentar';

    public function handle(): int
    {
        $coveId = $this->argument('cove');
        $revert = $this->option('revert');

        if ($revert) {
            return $this->revertCove($coveId);
        }

        return $this->forceEdoc($coveId);
    }

    private function forceEdoc(?int $coveId): int
    {
        $query = Cove::where('status', 'enviado')
            ->where(function ($q) {
                $q->whereNull('edocument')
                  ->orWhere('edocument', 'PENDIENTE');
            });

        if ($coveId) {
            $query->where('id', $coveId);
        }

        $coves = $query->get();

        if ($coves->isEmpty()) {
            $this->info('No hay COVEs atorados en estado "enviado".');
            return self::SUCCESS;
        }

        $this->warn("Se encontraron {$coves->count()} COVE(s) atorado(s).");

        $procesados = 0;
        foreach ($coves as $cove) {
            // Si tiene numero_operacion real, advertir y pedir confirmacion
            if ($cove->numero_operacion) {
                $this->warn("  COVE #{$cove->id}: TIENE numero_operacion ({$cove->numero_operacion}) real de VUCEM.");
                $this->warn("  Forzar e-Document SIMULADO hara que pierdas el seguimiento real.");
                if ($this->input->isInteractive() && !$this->confirm('  Deseas continuar con e-Document simulado?')) {
                    $this->line("  Saltando COVE #{$cove->id}");
                    continue;
                }
            }

            $dummyEdoc = 'COVE' . date('Ymd') . rand(1000, 9999);

            $cove->update([
                'status'           => 'procesado',
                'edocument'        => $dummyEdoc,
                'error_mensaje'    => null,
                'xml_respuesta'    => '<simulado>e-Document asignado manualmente por comando coves:force-edoc</simulado>',
            ]);

            $this->line("  COVE #{$cove->id} (factura {$cove->factura_numero}) -> e-Document: {$dummyEdoc}");
            $this->line('  ATENCION: Este e-Document es SIMULADO. No es valido ante VUCEM/SAT.');
            $procesados++;
        }

        $this->info("{$procesados} COVE(s) procesado(s) con e-Document simulado.");
        return self::SUCCESS;
    }

    private function revertCove(?int $coveId): int
    {
        $query = Cove::where('status', 'procesado');

        if ($coveId) {
            $query->where('id', $coveId);
        } else {
            $query->where('xml_respuesta', 'like', '%simulado%');
        }

        $coves = $query->get();

        if ($coves->isEmpty()) {
            $this->info('No hay COVEs procesados simulados que revertir.');
            return self::SUCCESS;
        }

        $this->warn("Se revertiran {$coves->count()} COVE(s) a estado 'enviado'.");
        if ($this->input->isInteractive() && !$this->confirm('Continuar?')) {
            $this->info('Operacion cancelada.');
            return self::SUCCESS;
        }

        foreach ($coves as $cove) {
            $cove->update([
                'status'            => 'enviado',
                'edocument'         => 'PENDIENTE',
                'error_mensaje'     => null,
                'intentos_consulta' => 0,
            ]);

            $this->line("  COVE #{$cove->id} (factura {$cove->factura_numero}) revertido a 'enviado' (intentos_consulta=0)");
        }

        $this->info("{$coves->count()} COVE(s) revertido(s) a estado enviado.");
        return self::SUCCESS;
    }
}
