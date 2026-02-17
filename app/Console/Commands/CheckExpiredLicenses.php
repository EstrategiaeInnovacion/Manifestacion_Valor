<?php

namespace App\Console\Commands;

use App\Mail\LicenseExpired;
use App\Models\License;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredLicenses extends Command
{
    protected $signature = 'licenses:check-expired';
    protected $description = 'Verifica licencias expiradas y envía notificación por correo al admin';

    public function handle(): int
    {
        // Buscar licencias expiradas que aún no han sido notificadas
        // (puede que isActive() ya las marcó como 'expired', o que aún estén 'active' pero vencidas)
        $expiredLicenses = License::where('expiry_notified', false)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'active')
                      ->where('expires_at', '<=', now());
                })->orWhere(function ($q) {
                    $q->where('status', 'expired');
                });
            })
            ->with('admin')
            ->get();

        if ($expiredLicenses->isEmpty()) {
            $this->info('No hay licencias pendientes de notificación de expiración.');
            return self::SUCCESS;
        }

        foreach ($expiredLicenses as $license) {
            // Asegurar que el status sea 'expired'
            if ($license->status === 'active') {
                $license->update(['status' => 'expired']);
            }

            $this->info("Licencia {$license->license_key} expirada para {$license->admin->full_name}");

            // Enviar correo de notificación
            try {
                $sent = (new LicenseExpired($license->admin, $license))->send();
                if ($sent) {
                    $license->update(['expiry_notified' => true]);
                    Log::info('[LICENSE] Correo de expiración enviado', [
                        'license_key' => $license->license_key,
                        'admin' => $license->admin->email,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[LICENSE] Error al enviar correo de expiración', [
                    'license_key' => $license->license_key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Se procesaron {$expiredLicenses->count()} licencia(s) expirada(s).");
        return self::SUCCESS;
    }
}
