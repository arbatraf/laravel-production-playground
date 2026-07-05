<?php

namespace Database\Factories;

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'event_type' => AuditEventType::TaskStatusChanged->value,
            'subject_type' => Task::class,
            'subject_id' => Task::factory(),
            'description' => 'Task status changed from open to in_progress.',
            'properties' => [
                'from_status' => 'open',
                'to_status' => 'in_progress',
            ],
            'request_id' => fake()->uuid(),
        ];
    }

    public function forSubject(Model $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }
}
