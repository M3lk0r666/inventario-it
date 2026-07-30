<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Extensión Zoom" y "Correo institucional" dejan de ser bienes adicionales:
 *  - la extensión Zoom pasa a ser un dato propio del empleado (columna nueva);
 *  - el correo institucional ya se captura en el correo del empleado.
 * Se desactivan esos dos tipos (no se borran para conservar el histórico de cartas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('zoom_extension', 20)->nullable()->after('phone')
                ->comment('Extensión de Zoom asignada al empleado');
        });

        DB::table('additional_item_types')
            ->whereIn('name', ['Extensión Zoom', 'Correo institucional'])
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('zoom_extension');
        });

        DB::table('additional_item_types')
            ->whereIn('name', ['Extensión Zoom', 'Correo institucional'])
            ->update(['is_active' => true]);
    }
};
