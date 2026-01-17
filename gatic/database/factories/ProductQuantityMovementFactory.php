<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductQuantityMovement>
 */
class ProductQuantityMovementFactory extends Factory
{
    protected $model = ProductQuantityMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qtyBefore = fake()->numberBetween(10, 100);
        $direction = fake()->randomElement(ProductQuantityMovement::DIRECTIONS);
        $qty = fake()->numberBetween(1, 10);
        $qtyAfter = $direction === ProductQuantityMovement::DIRECTION_OUT
            ? $qtyBefore - $qty
            : $qtyBefore + $qty;

        return [
            'product_id' => Product::factory(),
            'employee_id' => Employee::factory(),
            'actor_user_id' => User::factory(),
            'direction' => $direction,
            'qty' => $qty,
            'qty_before' => $qtyBefore,
            'qty_after' => $qtyAfter,
            'note' => fake()->sentence(),
        ];
    }
}
