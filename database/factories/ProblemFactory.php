<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\ProblemCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Problem> */
class ProblemFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['new', 'in_progress', 'resolved', 'closed']);
        $reportedAt = fake()->dateTimeBetween('-6 months', '-1 day');
        $resolvedAt = in_array($status, ['resolved', 'closed'])
            ? fake()->dateTimeBetween($reportedAt, 'now') : null;

        return [
            'title' => fake()->randomElement([
                'No enciende el equipo', 'Pantalla parpadea', 'Impresora atasca papel',
                'Disco duro con ruido', 'Teclado con teclas dañadas', 'Sobrecalentamiento',
                'Falla en puerto de red', 'Sistema operativo corrupto', 'Batería no carga',
            ]),
            'description' => fake()->sentence(12),
            'problem_category_id' => ProblemCategory::inRandomOrder()->value('id'),
            'asset_id' => Asset::inRandomOrder()->value('id'),
            'priority' => fake()->randomElement(['low', 'medium', 'medium', 'high', 'critical']),
            'status' => $status,
            'cost' => fake()->optional(0.5)->randomFloat(2, 100, 8000),
            'reported_at' => $reportedAt,
            'resolved_at' => $resolvedAt,
            'closed_at' => $status === 'closed' ? $resolvedAt : null,
            'created_by' => User::inRandomOrder()->value('id'),
        ];
    }
}
