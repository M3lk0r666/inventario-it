<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de asignación: definitiva o préstamo temporal (con fecha estimada
 * de devolución). Permite distinguir equipos prestados de los definitivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('assignment_type', 20)->default('permanent')->after('condition_on_assign')
                ->comment('permanent = definitiva, loan = préstamo temporal');
            $table->date('expected_return_at')->nullable()->after('assignment_type')
                ->comment('Fecha estimada de devolución (préstamos)');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'expected_return_at']);
        });
    }
};
