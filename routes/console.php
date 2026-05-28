<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verificar licencias expiradas cada minuto
Schedule::command('licenses:check-expired')->everyMinute();

// Verificar sellos VUCEM próximos a vencer — notificación 30 días antes (una vez al día)
Schedule::command('seals:check-expiry')->dailyAt('08:00');

// Limpieza diaria de logs a medianoche (archivos de log + tabla vucem_error_logs)
Schedule::command('logs:limpiar')->dailyAt('00:00');

// Registrar el comando de diagnóstico si no hay Kernel manual
// (Los comandos en app/Console/Commands se auto-descubren por Laravel)
