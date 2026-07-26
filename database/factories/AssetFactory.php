<?php

namespace Database\Factories;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\AssetType;
use App\Models\Location;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Asset> */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        static $tag = 1;

        $model = AssetModel::inRandomOrder()->first();
        $type = $model?->type ?? AssetType::inRandomOrder()->first();

        return [
            'asset_tag' => 'INV-'.str_pad((string) $tag++, 5, '0', STR_PAD_LEFT),
            'name' => trim(($model?->manufacturer?->name ?? '').' '.($model?->name ?? $type->name)),
            'asset_type_id' => $type->id,
            'asset_model_id' => $model?->id,
            'serial_number' => strtoupper(fake()->bothify('??#####??##')),
            'asset_status_id' => AssetStatus::inRandomOrder()->value('id'),
            'location_id' => Location::inRandomOrder()->value('id'),
            'supplier_id' => Supplier::inRandomOrder()->value('id'),
            'purchase_date' => fake()->dateTimeBetween('-4 years', '-1 month'),
            'purchase_cost' => fake()->randomFloat(2, 800, 45000),
            'warranty_expires_at' => fake()->dateTimeBetween('-1 year', '+2 years'),
            'specs' => $this->specsFor($type->slug),
        ];
    }

    private function specsFor(string $typeSlug): ?array
    {
        return match ($typeSlug) {
            'desktop', 'laptop', 'server' => [
                'cpu' => fake()->randomElement(['Intel Core i5-1235U', 'Intel Core i7-1355U', 'AMD Ryzen 5 5600U', 'Intel Xeon E-2314']),
                'ram' => fake()->randomElement(['8 GB', '16 GB', '32 GB']),
                'storage' => fake()->randomElement(['256 GB SSD', '512 GB SSD', '1 TB SSD', '1 TB HDD']),
                'os' => fake()->randomElement(['Windows 11 Pro', 'Windows 10 Pro', 'Ubuntu 24.04']),
            ],
            'monitor' => [
                'size' => fake()->randomElement(['21.5"', '24"', '27"']),
                'resolution' => fake()->randomElement(['1920x1080', '2560x1440']),
                'connectors' => 'HDMI, DisplayPort',
            ],
            'printer' => [
                'technology' => fake()->randomElement(['Láser monocromo', 'Láser color', 'Inyección de tinta']),
                'connectivity' => fake()->randomElement(['USB', 'USB + Red', 'USB + Red + WiFi']),
            ],
            default => null,
        };
    }
}
