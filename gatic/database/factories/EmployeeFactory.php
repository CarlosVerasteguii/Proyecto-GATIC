<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rpe' => fake()->unique()->numerify('RPE-#####'),
            'name' => fake()->name(),
            'department' => fake()->optional()->word(),
            'job_title' => fake()->optional()->jobTitle(),
        ];
    }
}
