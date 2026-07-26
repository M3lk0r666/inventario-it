<?php

namespace Database\Factories;

use App\Models\LicenseType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\License> */
class LicenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'software_name' => fake()->unique()->randomElement([
                'Microsoft 365 Empresa', 'Windows 11 Pro', 'Adobe Creative Cloud',
                'AutoCAD LT', 'Antivirus ESET Endpoint', 'CorelDRAW', 'TeamViewer',
                'Visual Studio Professional', 'SQL Server Standard', 'Zoom Workplace',
            ]),
            'version' => fake()->optional()->numerify('202#'),
            'license_type_id' => LicenseType::inRandomOrder()->value('id'),
            'supplier_id' => Supplier::inRandomOrder()->value('id'),
            'seats' => fake()->randomElement([1, 5, 10, 25, 50]),
            'product_key' => strtoupper(fake()->bothify('?????-?????-?????-?????-?????')),
            'purchase_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'cost' => fake()->randomFloat(2, 500, 60000),
            'expires_at' => fake()->optional(0.7)->dateTimeBetween('-2 months', '+14 months'),
        ];
    }
}
