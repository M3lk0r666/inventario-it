<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Consumable> */
class ConsumableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Tóner HP 85A', 'Tóner HP 26A', 'Tóner Brother TN-450', 'Tinta Epson 664 Negro',
                'Tinta Epson 664 Cyan', 'Mouse USB genérico', 'Teclado USB genérico',
                'Cable HDMI 2m', 'Cable de red Cat6 3m', 'Batería CR2032', 'Memoria USB 32GB',
                'Adaptador USB-C a HDMI', 'Limpiador de pantallas', 'Aire comprimido',
            ]),
            'stock' => fake()->numberBetween(0, 30),
            'min_stock' => fake()->numberBetween(2, 5),
            'unit' => 'pieza',
            'location_id' => Location::inRandomOrder()->value('id'),
            'supplier_id' => Supplier::inRandomOrder()->value('id'),
        ];
    }
}
