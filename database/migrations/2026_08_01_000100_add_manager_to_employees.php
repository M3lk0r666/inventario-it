<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jefe inmediato del empleado (auto-referencia a employees). Se usa en las
 * cartas responsivas y para notificar por correo los movimientos de bienes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('manager_id')->nullable()->after('location_id')
                ->constrained('employees')->nullOnDelete()
                ->comment('Jefe inmediato (otro empleado)');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
        });
    }
};
