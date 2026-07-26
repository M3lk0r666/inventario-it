<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos base del sistema (patrón GLPI: dropdowns administrables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nombre del departamento');
            $table->string('code', 20)->nullable()->comment('Clave interna');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nombre de la ubicación (edificio/piso/sucursal)');
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Fabricante/marca');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Tipo de bien: Laptop, Monitor, Impresora...');
            $table->string('slug')->unique()->comment('Identificador para rutas y specs');
            $table->string('icon', 50)->nullable()->comment('Clase de icono (Remix Icon)');
            $table->json('spec_fields')->nullable()->comment('Definición de campos dinámicos de specs por tipo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Estado: Operativo, En reparación, Baja...');
            $table->string('slug')->unique();
            $table->string('color', 20)->nullable()->comment('Color para badges en UI');
            $table->boolean('is_assignable')->default(true)->comment('Si un activo en este estado puede asignarse');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Modelo comercial, p.ej. Latitude 5440');
            $table->foreignId('manufacturer_id')->constrained()->restrictOnDelete();
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['name', 'manufacturer_id']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Razón social o nombre comercial');
            $table->string('rfc', 20)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('license_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Perpetua, Suscripción anual, OEM...');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('problem_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Hardware, Software, Red...');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problem_categories');
        Schema::dropIfExists('license_types');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('asset_models');
        Schema::dropIfExists('asset_statuses');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('manufacturers');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('departments');
    }
};
