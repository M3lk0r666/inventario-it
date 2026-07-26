<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licencias de software y su asignación polimórfica a equipos o empleados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('software_name')->comment('Software licenciado, p.ej. Microsoft 365');
            $table->string('version', 50)->nullable();
            $table->foreignId('license_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('seats')->default(1)->comment('Asientos totales contratados');
            $table->text('product_key')->nullable()->comment('Clave(s) de producto');
            $table->date('purchase_date')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->date('expires_at')->nullable()->comment('Fecha de expiración (null = perpetua)');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('software_name');
            $table->index('expires_at');
        });

        Schema::create('license_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->morphs('assignable'); // Asset o Employee
            $table->date('assigned_at');
            $table->date('released_at')->nullable()->comment('null = asiento en uso');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['license_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_assignments');
        Schema::dropIfExists('licenses');
    }
};
