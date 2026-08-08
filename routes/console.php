<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Digest diario de alertas de inventario (requiere cron ejecutando el scheduler).
Schedule::command('alerts:digest')->weekdays()->dailyAt('08:00');

// Avanza los recordatorios recurrentes vencidos a su siguiente ocurrencia.
Artisan::command('reminders:roll', function () {
    $now = now();
    $count = 0;

    \App\Models\Reminder::where('recurrence', '!=', 'none')
        ->whereNotNull('recurrence')
        ->where('starts_at', '<', $now)
        ->chunkById(200, function ($reminders) use ($now, &$count) {
            foreach ($reminders as $reminder) {
                $start = $reminder->starts_at;
                $end = $reminder->ends_at;
                $guard = 0;

                while ($start->lt($now) && $guard < 10000) {
                    $start = $reminder->advance($start);
                    if ($end) {
                        $end = $reminder->advance($end);
                    }
                    $guard++;
                }

                $reminder->update(['starts_at' => $start, 'ends_at' => $end]);
                $count++;
            }
        });

    $this->info("Recordatorios recurrentes avanzados: {$count}");
})->purpose('Avanza los recordatorios recurrentes vencidos a su siguiente ocurrencia');

Schedule::command('reminders:roll')->hourly();
