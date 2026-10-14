<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_task_can_be_added_to_an_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('tasks.store', $project), [
                'title' => 'Audit current pages',
                'priority' => TaskPriority::High->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Audit current pages',
            'priority' => TaskPriority::High->value,
        ]);
    }

    public function test_a_task_cannot_be_added_to_someone_elses_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(User::factory())->create();

        $this->actingAs($user)
            ->post(route('tasks.store', $project), ['title' => 'Sneaky task'])
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', ['title' => 'Sneaky task']);
    }

    public function test_labels_are_created_for_the_acting_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('tasks.store', $project), [
                'title' => 'Build the component library',
                'labels' => ['frontend', 'design'],
            ]);

        $task = Task::firstWhere('title', 'Build the component library');

        $this->assertCount(2, $task->labels);
        $this->assertSame([$user->id, $user->id], $task->labels->pluck('user_id')->all());
    }

    public function test_an_identically_named_label_from_another_user_is_never_reused(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $foreignLabel = Label::factory()->for(User::factory())->create(['name' => 'frontend']);

        $this->actingAs($user)
            ->post(route('tasks.store', $project), [
                'title' => 'Rework onboarding',
                'labels' => ['frontend'],
            ]);

        $task = Task::firstWhere('title', 'Rework onboarding');

        $this->assertNotEquals($foreignLabel->id, $task->labels->first()->id);
        $this->assertSame($user->id, $task->labels->first()->user_id);
    }

    public function test_the_status_endpoint_moves_a_task_and_stamps_completion(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for(Project::factory()->for($user))->create();

        $this->actingAs($user)
            ->patch(route('tasks.status', $task), ['status' => TaskStatus::Done->value])
            ->assertRedirect();

        $task->refresh();

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_another_users_task_cannot_be_touched(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for(Project::factory()->for(User::factory()))->create();

        $this->actingAs($user)->patch(route('tasks.update', $task), ['title' => 'Hijacked'])->assertForbidden();
        $this->actingAs($user)->patch(route('tasks.status', $task), ['status' => TaskStatus::Done->value])->assertForbidden();
        $this->actingAs($user)->delete(route('tasks.destroy', $task))->assertForbidden();
    }

    public function test_tasks_can_be_filtered(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->for($project)->overdue()->create(['title' => 'Late task']);
        Task::factory()->for($project)->create(['title' => 'Fresh task', 'due_date' => today()->addWeek()]);

        $this->actingAs($user)
            ->get(route('tasks.index', ['overdue' => 1]))
            ->assertOk()
            ->assertSee('Late task')
            ->assertDontSee('Fresh task');
    }
}
