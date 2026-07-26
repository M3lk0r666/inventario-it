<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Herramientas: recordatorios y base de conocimientos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private')
                ->comment('private = solo el autor, public = todos los usuarios');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Autor');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kb_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kb_category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->comment('Contenido con texto enriquecido');
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Autor');
            $table->timestamps();
            $table->softDeletes();
        });

        // Índice fulltext solo en MySQL (SQLite de las pruebas no lo soporta).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('kb_articles', function (Blueprint $table) {
                $table->fullText(['title', 'body'], 'kb_articles_fulltext');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_articles');
        Schema::dropIfExists('kb_categories');
        Schema::dropIfExists('reminders');
    }
};
