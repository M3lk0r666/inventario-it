<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consumibles con stock y su kardex de entradas/salidas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre del consumible, p.ej. Tóner HP 85A');
            $table->text('description')->nullable();
            $table->unsignedInteger('stock')->default(0)->comment('Existencia actual');
            $table->unsignedInteger('min_stock')->default(0)->comment('Mínimo para alerta de stock bajo');
            $table->string('unit', 30)->default('pieza')->comment('Unidad de medida');
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
        });

        Schema::create('consumable_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out'])->comment('in = entrada, out = salida');
            $table->unsignedInteger('quantity');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Destinatario (en salidas)');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Usuario que registró el movimiento');
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('Costo unitario (en entradas)');
            $table->dateTime('moved_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['consumable_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_movements');
        Schema::dropIfExists('consumables');
    }
};
