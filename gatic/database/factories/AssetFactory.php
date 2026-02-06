<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shouldGenerateAssetTag = fake()->boolean(50);

        return [
            'product_id' => Product::factory(),
            'location_id' => Location::factory(),
            'current_employee_id' => null,
            'serial' => fake()->unique()->uuid(),
            'asset_tag' => $shouldGenerateAssetTag
                ? fake()->unique()->bothify('TAG-????-####')
                : null,
            'status' => Asset::STATUS_AVAILABLE,
            'useful_life_months' => null,
            'expected_replacement_date' => null,
        ];
    }
}
