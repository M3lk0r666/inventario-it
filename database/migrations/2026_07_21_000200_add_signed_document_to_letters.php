<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evidencia de firma: la carta pasa a "firmada" al subir el documento
 * físicamente firmado (escaneo/foto), con fecha y responsable del registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responsive_letters', function (Blueprint $table) {
            $table->string('signed_document_path')->nullable()->after('pdf_path')
                ->comment('Escaneo/foto de la carta firmada por el empleado');
            $table->timestamp('signed_at')->nullable()->after('signed_document_path');
            $table->foreignId('signed_by')->nullable()->after('signed_at')
                ->constrained('users')->nullOnDelete()->comment('Usuario que registró la firma');
        });
    }

    public function down(): void
    {
        Schema::table('responsive_letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by');
            $table->dropColumn(['signed_document_path', 'signed_at']);
        });
    }
};
