<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notable_type' => Task::class,
            'notable_id' => Task::factory(),
            'author_id' => User::factory()->manager(),
            'body' => fake()->sentence(8),
        ];
    }
}
