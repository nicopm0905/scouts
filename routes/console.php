<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Tareas programadas (Agente H). En producción, un único cron ejecuta el scheduler:
|   * * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1
*/

// Recordatorios de pago (X días antes del vencimiento y al vencer). Cada mañana.
Schedule::command('charges:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Alertas de caducidad (documentos, certificados, revisiones de inventario). Cada mañana.
Schedule::command('alerts:expiry')
    ->dailyAt('08:15')
    ->withoutOverlapping();
