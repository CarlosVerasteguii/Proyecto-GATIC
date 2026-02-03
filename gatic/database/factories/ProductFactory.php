<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->word(),
            'category_id' => Category::factory()->state([
                'is_serialized' => false,
                'requires_asset_tag' => false,
            ]),
            'brand_id' => Brand::factory(),
            'qty_total' => fake()->numberBetween(0, 100),
            'low_stock_threshold' => null,
        ];
    }

    /**
     * Configure the product with a low stock threshold.
     */
    public function withLowStockThreshold(int $threshold = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'low_stock_threshold' => $threshold,
        ]);
    }
}
