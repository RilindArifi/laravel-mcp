<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskFlowDemoSeeder extends Seeder
{
    /**
     * Seed a deterministic demo workspace plus a second user used to prove data isolation.
     */
    public function run(): void
    {
        $demo = $this->user('Demo User', 'demo@taskflow.test');

        $labels = $this->labels($demo, ['backend', 'frontend', 'design', 'research', 'external']);

        foreach ($this->workspace() as $definition) {
            $project = $demo->projects()->create([
                'name' => $definition['name'],
                'description' => $definition['description'],
                'status' => $definition['status'],
                'due_date' => $definition['due_in_days'] === null
                    ? null
                    : today()->addDays($definition['due_in_days']),
            ]);

            foreach ($definition['tasks'] as $task) {
                $this->task($project, $task, $labels);
            }
        }

        $ada = $this->user('Ada Lovelace', 'ada@taskflow.test');

        $adaProject = $ada->projects()->create([
            'name' => 'Analytical Engine',
            'description' => 'Belongs to another user. The demo workspace must never see it.',
            'status' => ProjectStatus::Active,
            'due_date' => today()->addDays(30),
        ]);

        $adaProject->tasks()->createMany([
            ['title' => 'Draft note G', 'status' => TaskStatus::InProgress, 'priority' => TaskPriority::High],
            ['title' => 'Compute Bernoulli numbers', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Medium],
        ]);
    }

    /**
     * Create or update a verified demo user with a known password.
     */
    protected function user(string $name, string $email): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * Create the given labels for the user.
     *
     * @param  array<int, string>  $names
     * @return array<string, Label>
     */
    protected function labels(User $user, array $names): array
    {
        $colors = ['backend' => 'blue', 'frontend' => 'violet', 'design' => 'rose', 'research' => 'amber', 'external' => 'slate'];

        $labels = [];

        foreach ($names as $name) {
            $labels[$name] = $user->labels()->create([
                'name' => $name,
                'color' => $colors[$name] ?? 'slate',
            ]);
        }

        return $labels;
    }

    /**
     * Create a single task from its definition.
     *
     * @param  array{title: string, status: TaskStatus, priority: TaskPriority, due_in_days?: int|null, labels?: array<int, string>}  $definition
     * @param  array<string, Label>  $labels
     */
    protected function task(Project $project, array $definition, array $labels): void
    {
        $status = $definition['status'];
        $dueInDays = $definition['due_in_days'] ?? null;

        $task = $project->tasks()->create([
            'title' => $definition['title'],
            'description' => fake()->sentence(10),
            'status' => $status,
            'priority' => $definition['priority'],
            'due_date' => $dueInDays === null ? null : today()->addDays($dueInDays),
            'completed_at' => $status === TaskStatus::Done ? now()->subDays(fake()->numberBetween(0, 3)) : null,
        ]);

        $attach = array_values(array_filter(array_map(
            fn (string $name): ?int => $labels[$name]->id ?? null,
            $definition['labels'] ?? [],
        )));

        if ($attach !== []) {
            $task->labels()->attach($attach);
        }
    }

    /**
     * Get the deterministic demo workspace definition.
     *
     * @return array<int, array{name: string, description: string, status: ProjectStatus, due_in_days: int|null, tasks: array<int, array{title: string, status: TaskStatus, priority: TaskPriority, due_in_days?: int|null, labels?: array<int, string>}>}>
     */
    protected function workspace(): array
    {
        return [
            [
                'name' => 'Website Redesign',
                'description' => 'Rebuild the marketing site on the new design system.',
                'status' => ProjectStatus::Active,
                'due_in_days' => 21,
                'tasks' => [
                    ['title' => 'Audit current pages', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Medium, 'due_in_days' => -12, 'labels' => ['research']],
                    ['title' => 'Agree on the colour palette', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Low, 'due_in_days' => -6, 'labels' => ['design']],
                    ['title' => 'Build the component library', 'status' => TaskStatus::InProgress, 'priority' => TaskPriority::High, 'due_in_days' => 3, 'labels' => ['frontend', 'design']],
                    ['title' => 'Migrate the blog content', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Medium, 'due_in_days' => -2, 'labels' => ['backend']],
                    ['title' => 'Wait for legal to approve the copy', 'status' => TaskStatus::Blocked, 'priority' => TaskPriority::Medium, 'due_in_days' => -5, 'labels' => ['external']],
                    ['title' => 'Set up redirects', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Low, 'due_in_days' => 14, 'labels' => ['backend']],
                    ['title' => 'Run an accessibility pass', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::High, 'due_in_days' => 6, 'labels' => ['frontend']],
                ],
            ],
            [
                'name' => 'Mobile App v2',
                'description' => 'Ship the offline-first rewrite of the mobile client.',
                'status' => ProjectStatus::Active,
                'due_in_days' => 45,
                'tasks' => [
                    ['title' => 'Design the offline sync protocol', 'status' => TaskStatus::Done, 'priority' => TaskPriority::High, 'due_in_days' => -9, 'labels' => ['backend', 'research']],
                    ['title' => 'Implement the local cache', 'status' => TaskStatus::InProgress, 'priority' => TaskPriority::Urgent, 'due_in_days' => 2, 'labels' => ['backend']],
                    ['title' => 'Rework the onboarding screens', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Medium, 'due_in_days' => 10, 'labels' => ['design']],
                    ['title' => 'Fix the push notification crash', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Urgent, 'due_in_days' => -1, 'labels' => ['frontend']],
                    ['title' => 'Write the release notes', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Low, 'due_in_days' => 40],
                ],
            ],
            [
                'name' => 'Q4 Marketing Campaign',
                'description' => 'Paused until the new budget is approved.',
                'status' => ProjectStatus::OnHold,
                'due_in_days' => 60,
                'tasks' => [
                    ['title' => 'Draft the campaign brief', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Medium, 'due_in_days' => -20, 'labels' => ['research']],
                    ['title' => 'Book the ad placements', 'status' => TaskStatus::Blocked, 'priority' => TaskPriority::High, 'due_in_days' => 30, 'labels' => ['external']],
                    ['title' => 'Produce the landing page', 'status' => TaskStatus::Todo, 'priority' => TaskPriority::Medium, 'due_in_days' => 35, 'labels' => ['frontend']],
                ],
            ],
            [
                'name' => 'Internal Tooling',
                'description' => 'Finished work kept around for reporting.',
                'status' => ProjectStatus::Completed,
                'due_in_days' => null,
                'tasks' => [
                    ['title' => 'Automate the release checklist', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Medium, 'due_in_days' => -30, 'labels' => ['backend']],
                    ['title' => 'Add deploy notifications', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Low, 'due_in_days' => -25, 'labels' => ['backend']],
                    ['title' => 'Document the on-call rotation', 'status' => TaskStatus::Done, 'priority' => TaskPriority::Low, 'due_in_days' => -18],
                ],
            ],
        ];
    }
}
