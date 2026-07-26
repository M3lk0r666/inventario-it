<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha de renovación (distinta de la expiración): fecha límite para
 * renovar antes de que la licencia deje de funcionar. Con alerta configurable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->date('renewal_date')->nullable()->after('expires_at')
                ->comment('Fecha límite para renovar antes de expirar');
            $table->boolean('alerts_enabled')->default(true)->after('renewal_date')
                ->comment('Si se generan alertas de renovación');
            $table->unsignedSmallInteger('alert_days_before')->default(30)->after('alerts_enabled')
                ->comment('Días de anticipación para la alerta');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn(['renewal_date', 'alerts_enabled', 'alert_days_before']);
        });
    }
};
