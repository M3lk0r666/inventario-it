<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder por defecto (php artisan db:seed).
 *
 * Ejecuta según el entorno:
 *  - Producción → solo datos base (ProductionSeeder).
 *  - Desarrollo → base + datos de prueba (DemoSeeder).
 *
 * También puedes elegir explícitamente cuál correr:
 *  - php artisan db:seed --class=ProductionSeeder   (solo base, sin empleados ni pruebas)
 *  - php artisan db:seed --class=DemoSeeder          (solo datos de prueba; requiere base ya sembrada)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            // Producción: solo datos base (sin empleados ni datos de prueba).
            $this->call(ProductionSeeder::class);
        } else {
            // Desarrollo: DemoSeeder ya asegura la base + agrega datos de prueba.
            $this->call(DemoSeeder::class);
        }
    }
}
