<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verificar licencias expiradas cada minuto
Schedule::command('licenses:check-expired')->everyMinute();

// Registrar el comando de diagnóstico si no hay Kernel manual
// (Los comandos en app/Console/Commands se auto-descubren por Laravel)
