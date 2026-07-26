<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla única de bienes informáticos. Los campos variables por tipo van en specs (JSON).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 50)->unique()->comment('Etiqueta / número de inventario interno');
            $table->string('name')->comment('Nombre descriptivo del bien');
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('asset_model_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Modelo (el fabricante se obtiene vía el modelo)');
            $table->string('serial_number')->nullable()->comment('Número de serie del fabricante');
            $table->foreignId('asset_status_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable()->comment('Costo de compra');
            $table->date('warranty_expires_at')->nullable()->comment('Garantía vigente hasta');
            $table->json('specs')->nullable()->comment('Campos por tipo: CPU, RAM, disco, SO...');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('serial_number');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
