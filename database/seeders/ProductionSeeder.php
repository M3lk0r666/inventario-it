<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Datos BASE para producción (sin datos de prueba):
 * catálogos, roles/permisos, usuarios de acceso y configuración.
 *
 *   php artisan db:seed --class=ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
