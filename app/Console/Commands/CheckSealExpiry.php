<?php

namespace App\Console\Commands;

use App\Mail\SealExpiryWarning;
use App\Models\MvClientApplicant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSealExpiry extends Command
{
    protected $signature = 'seals:check-expiry';
    protected $description = 'Verifica sellos VUCEM próximos a vencer y envía notificación 30 días antes';

    public function handle(): int
    {
        $threshold = Carbon::today()->addDays(30);

        // Solicitantes cuyo certificado vence en los próximos 30 días
        // y cuya notificación aún no se ha enviado
        $applicants = MvClientApplicant::whereNotNull('vucem_cert_vigencia')
            ->where('seal_expiry_notified', false)
            ->whereDate('vucem_cert_vigencia', '<=', $threshold)
            ->whereDate('vucem_cert_vigencia', '>=', Carbon::today())
            ->get();

        if ($applicants->isEmpty()) {
            $this->info('No hay sellos próximos a vencer pendientes de notificación.');
            return self::SUCCESS;
        }

        foreach ($applicants as $applicant) {
            $daysLeft = (int) Carbon::today()->diffInDays($applicant->vucem_cert_vigencia, false);
            $daysLeft = max(0, $daysLeft);

            // Obtener el usuario responsable (quien creó el solicitante)
            $user = null;
            if ($applicant->created_by_user_id) {
                $user = User::find($applicant->created_by_user_id);
            }
            if (!$user) {
                $user = User::where('email', $applicant->user_email)->first();
            }

            if (!$user) {
                $this->warn("Solicitante #{$applicant->id} ({$applicant->business_name}): no se encontró usuario para notificar.");
                continue;
            }

            $this->info("Notificando vencimiento de sello: {$applicant->business_name} — {$daysLeft} día(s) restante(s) → {$user->email}");

            try {
                $sent = (new SealExpiryWarning($user, $applicant, $daysLeft))->send();
                if ($sent) {
                    $applicant->update(['seal_expiry_notified' => true]);
                    Log::info('[SEALS] Notificación de vencimiento enviada', [
                        'applicant_id' => $applicant->id,
                        'business_name' => $applicant->business_name,
                        'vigencia' => $applicant->vucem_cert_vigencia,
                        'days_left' => $daysLeft,
                        'notified_user' => $user->email,
                    ]);
                } else {
                    $this->warn("No se pudo enviar el correo para {$applicant->business_name}.");
                }
            } catch (\Exception $e) {
                Log::error('[SEALS] Error al enviar notificación de vencimiento', [
                    'applicant_id' => $applicant->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error al notificar {$applicant->business_name}: {$e->getMessage()}");
            }
        }

        $this->info("Se procesaron {$applicants->count()} sello(s) próximos a vencer.");
        return self::SUCCESS;
    }
}
