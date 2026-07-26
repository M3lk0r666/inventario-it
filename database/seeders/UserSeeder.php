<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Crea las 4 cuentas base con una contraseña ALEATORIA (no utilizable).
     * El administrador define la contraseña de cada una después con:
     *   php artisan user:password {correo}
     * El Super Admin de arranque queda marcado como protegido (no eliminable).
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador del Sistema', 'email' => 'admin@inventario.test', 'role' => 'Super Admin', 'protected' => true],
            ['name' => 'Admin de Inventario', 'email' => 'inventario@inventario.test', 'role' => 'Administrador de Inventario', 'protected' => false],
            ['name' => 'Técnico de Soporte', 'email' => 'tecnico@inventario.test', 'role' => 'Técnico', 'protected' => false],
            ['name' => 'Usuario de Consulta', 'email' => 'consulta@inventario.test', 'role' => 'Consulta', 'protected' => false],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Str::password(40), // aleatoria: se fija luego con user:password
                    'email_verified_at' => now(),
                    'is_protected' => $data['protected'],
                ]
            );
            $user->syncRoles([$data['role']]);

            if ($user->is_protected !== $data['protected']) {
                $user->update(['is_protected' => $data['protected']]);
            }
        }

        $this->command?->warn('Usuarios creados SIN contraseña utilizable. Define cada una con: php artisan user:password <correo>');
    }
}
