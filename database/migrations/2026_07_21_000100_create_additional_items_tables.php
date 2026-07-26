<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bienes adicionales (llaves, controles, huella, extensión Zoom, correo...)
 * que se entregan/reciben junto con el equipo y se listan en la carta.
 * Además: tipo de carta (entrega/recepción) y vínculo de devolución.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_item_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Llave de acceso, Control vehicular, Extensión Zoom...');
            $table->boolean('requires_value')->default(false)
                ->comment('Si lleva un dato asociado (p.ej. número de extensión, correo)');
            $table->string('value_label')->nullable()->comment('Etiqueta del dato, p.ej. "Extensión"');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('letter_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsive_letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('additional_item_type_id')->constrained()->restrictOnDelete();
            $table->string('value')->nullable()->comment('Valor capturado (extensión, correo...)');
            $table->timestamps();
            $table->unique(['responsive_letter_id', 'additional_item_type_id'], 'letter_item_unique');
        });

        Schema::table('responsive_letters', function (Blueprint $table) {
            $table->enum('type', ['delivery', 'return'])->default('delivery')->after('folio')
                ->comment('delivery = entrega/carta responsiva, return = recepción/salida');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('return_letter_id')->nullable()->after('responsive_letter_id')
                ->constrained('responsive_letters')->nullOnDelete()
                ->comment('Carta de recepción que ampara la devolución');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('return_letter_id');
        });
        Schema::table('responsive_letters', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::dropIfExists('letter_items');
        Schema::dropIfExists('additional_item_types');
    }
};
