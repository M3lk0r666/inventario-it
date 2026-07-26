<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de envíos de artículos de la base de conocimientos por correo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_article_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Quién compartió');
            $table->json('recipients')->comment('Direcciones a las que se envió');
            $table->text('message')->nullable()->comment('Mensaje del remitente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_article_shares');
    }
};
