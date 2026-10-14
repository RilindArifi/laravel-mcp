<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => ucfirst(rtrim(fake()->sentence(4), '.')),
            'description' => fake()->sentence(10),
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
            'due_date' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the task is being worked on.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    /**
     * Indicate that the task is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Blocked,
        ]);
    }

    /**
     * Indicate that the task is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Done,
            'completed_at' => now()->subDays(fake()->numberBetween(0, 3)),
        ]);
    }

    /**
     * Indicate that the task is unfinished and past its due date.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaskStatus::Todo,
            'due_date' => today()->subDays(fake()->numberBetween(1, 10)),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the task is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => TaskPriority::Urgent,
        ]);
    }
}
