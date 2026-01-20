<?php

namespace Database\Factories;

use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\PendingTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendingTask>
 */
class PendingTaskFactory extends Factory
{
    protected $model = PendingTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(PendingTaskType::cases()),
            'description' => fake()->optional()->sentence(),
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PendingTaskStatus::Draft,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PendingTaskStatus::Ready,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PendingTaskStatus::Processing,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PendingTaskStatus::Completed,
        ]);
    }
}
