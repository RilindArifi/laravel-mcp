<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst(rtrim(fake()->unique()->sentence(3), '.')),
            'description' => fake()->sentence(12),
            'status' => ProjectStatus::Active,
            'due_date' => fake()->dateTimeBetween('+1 week', '+2 months'),
        ];
    }

    /**
     * Indicate that the project is on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::OnHold,
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::Completed,
        ]);
    }

    /**
     * Indicate that the project is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::Archived,
        ]);
    }
}
