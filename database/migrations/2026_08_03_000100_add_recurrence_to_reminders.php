<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurrencia de recordatorios: none, hourly, daily, weekly, monthly, yearly.
 * Al pasar la fecha, un comando programado avanza el recordatorio a su
 * siguiente ocurrencia (ver App\Console reminders:roll).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('recurrence', 20)->default('none')->after('visibility')
                ->comment('none, hourly, daily, weekly, monthly, yearly');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('recurrence');
        });
    }
};
