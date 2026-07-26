<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empleados (separados de users) y sus cuentas de acceso corporativas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 30)->unique()->comment('Número de empleado');
            $table->string('name')->comment('Nombre completo');
            $table->string('position')->nullable()->comment('Puesto');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Estado laboral');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()
                ->comment('Cuenta de acceso al sistema, si la tiene');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
        });

        Schema::create('employee_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('account_type', ['email', 'domain', 'vpn', 'system'])
                ->comment('Tipo: correo, dominio, VPN, sistema interno');
            $table->string('system_name')->nullable()->comment('Nombre del sistema (para tipo system)');
            $table->string('identifier')->comment('Usuario / correo de la cuenta');
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'account_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_accounts');
        Schema::dropIfExists('employees');
    }
};
