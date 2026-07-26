<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cartas responsivas y asignaciones activo ↔ empleado.
 * El histórico de un activo son todas sus assignments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsive_letters', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30)->unique()->comment('Folio consecutivo, p.ej. CR-2026-0001');
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('issued_at')->comment('Fecha de emisión');
            $table->string('pdf_path')->nullable()->comment('Ruta del PDF generado en storage');
            $table->enum('status', ['generated', 'signed', 'cancelled'])->default('generated')
                ->comment('Estado de firma / vigencia');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('responsive_letter_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Carta responsiva que ampara la entrega');
            $table->date('assigned_at')->comment('Fecha de entrega');
            $table->date('returned_at')->nullable()->comment('Fecha de devolución (null = asignación activa)');
            $table->string('condition_on_assign')->nullable()->comment('Estado físico al entregar');
            $table->string('condition_on_return')->nullable()->comment('Estado físico al devolver');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Usuario que registró la entrega');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Usuario que registró la devolución');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['asset_id', 'returned_at'], 'assignments_active_by_asset');
            $table->index(['employee_id', 'returned_at'], 'assignments_active_by_employee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('responsive_letters');
    }
};
