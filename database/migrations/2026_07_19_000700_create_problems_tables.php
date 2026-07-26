<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Problemas de soporte ligados a activos, con notas de seguimiento.
 * El histórico de cambios se lleva con activitylog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('problem_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete()
                ->comment('Activo afectado');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['new', 'in_progress', 'resolved', 'closed'])->default('new');
            $table->decimal('cost', 12, 2)->nullable()->comment('Costo de reparación');
            $table->dateTime('reported_at');
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Técnico responsable');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['asset_id', 'status']);
        });

        Schema::create('problem_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('problem_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('body')->comment('Nota con texto enriquecido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problem_notes');
        Schema::dropIfExists('problems');
    }
};
