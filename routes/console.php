<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Registrar el comando de diagnóstico si no hay Kernel manual
// (Los comandos en app/Console/Commands se auto-descubren por Laravel)
