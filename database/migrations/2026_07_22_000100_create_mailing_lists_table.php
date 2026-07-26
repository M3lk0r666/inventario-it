<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listas de correo / distribución (Office 365): nombre amigable + dirección.
 * La app envía a la lista y O365 reparte a sus miembros.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre amigable, p.ej. Todos los usuarios');
            $table->string('email')->comment('Dirección de la lista de distribución');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_lists');
    }
};
