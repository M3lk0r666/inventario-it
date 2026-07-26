<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Digest diario de alertas de inventario (requiere cron ejecutando el scheduler).
Schedule::command('alerts:digest')->weekdays()->dailyAt('08:00');
