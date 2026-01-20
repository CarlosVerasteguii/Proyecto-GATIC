<?php

namespace Database\Factories;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendingTaskLine>
 */
class PendingTaskLineFactory extends Factory
{
    protected $model = PendingTaskLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isSerialized = fake()->boolean();

        return [
            'pending_task_id' => PendingTask::factory(),
            'line_type' => $isSerialized ? PendingTaskLineType::Serialized : PendingTaskLineType::Quantity,
            'product_id' => Product::factory()->for(
                Category::factory()->state(['is_serialized' => $isSerialized]),
                'category'
            ),
            'serial' => $isSerialized ? fake()->unique()->regexify('[A-Z0-9]{8}') : null,
            'asset_tag' => $isSerialized ? fake()->unique()->regexify('[A-Z]{3}[0-9]{4}') : null,
            'quantity' => $isSerialized ? null : fake()->numberBetween(1, 100),
            'employee_id' => Employee::factory(),
            'note' => fake()->sentence(),
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    public function serialized(): static
    {
        return $this->state(fn (array $attributes) => [
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => Product::factory()->for(
                Category::factory()->state(['is_serialized' => true]),
                'category'
            ),
            'serial' => fake()->unique()->regexify('[A-Z0-9]{8}'),
            'asset_tag' => fake()->unique()->regexify('[A-Z]{3}[0-9]{4}'),
            'quantity' => null,
        ]);
    }

    public function quantity(int $qty = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => Product::factory()->for(
                Category::factory()->state(['is_serialized' => false]),
                'category'
            ),
            'serial' => null,
            'asset_tag' => null,
            'quantity' => $qty,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'line_status' => PendingTaskLineStatus::Pending,
        ]);
    }
}
