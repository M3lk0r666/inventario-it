<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Employee> */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        static $number = 1000;

        return [
            'employee_number' => 'EMP-'.str_pad((string) $number++, 4, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'position' => fake()->randomElement([
                'Analista', 'Coordinador', 'Gerente', 'Auxiliar administrativo',
                'Desarrollador', 'Contador', 'Vendedor', 'Soporte técnico',
            ]),
            'department_id' => Department::inRandomOrder()->value('id'),
            'location_id' => Location::inRandomOrder()->value('id'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('55########'),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }
}
