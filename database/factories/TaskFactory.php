<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'contact_id' => null,
            'assigned_to_user_id' => User::factory()->manager(),
            'created_by_user_id' => User::factory()->admin(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(8),
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Normal,
            'due_at' => fake()->optional()->dateTimeBetween('now', '+14 days'),
            'completed_at' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::InProgress,
            'completed_at' => null,
        ]);
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Waiting,
            'completed_at' => null,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TaskPriority::High,
        ]);
    }
}
