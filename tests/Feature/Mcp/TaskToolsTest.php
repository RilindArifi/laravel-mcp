<?php

namespace Tests\Feature\Mcp;

use App\Enums\TaskStatus;
use App\Mcp\Servers\TaskFlowServer;
use App\Mcp\Tools\CompleteTaskTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\DeleteTaskTool;
use App\Mcp\Tools\GenerateProgressReportTool;
use App\Mcp\Tools\ListTasksTool;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_task_can_be_created_with_labels(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        TaskFlowServer::actingAs($user)
            ->tool(CreateTaskTool::class, [
                'project_id' => $project->id,
                'title' => 'Build the component library',
                'priority' => 'high',
                'labels' => ['frontend'],
            ])
            ->assertOk()
            ->assertSee('Created task');

        $task = Task::firstWhere('title', 'Build the component library');

        $this->assertSame($project->id, $task->project_id);
        $this->assertSame('frontend', $task->labels->first()->name);
        $this->assertSame($user->id, $task->labels->first()->user_id);
    }

    public function test_a_task_cannot_be_created_in_another_users_project(): void
    {
        $user = User::factory()->create();
        $foreign = Project::factory()->for(User::factory())->create();

        TaskFlowServer::actingAs($user)
            ->tool(CreateTaskTool::class, [
                'project_id' => $foreign->id,
                'title' => 'Sneaky task',
            ])
            ->assertHasErrors();

        $this->assertDatabaseMissing('tasks', ['title' => 'Sneaky task']);
    }

    public function test_listing_tasks_can_be_narrowed_to_overdue_work(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->for($project)->overdue()->create(['title' => 'Late task']);
        Task::factory()->for($project)->create(['title' => 'Fresh task']);

        TaskFlowServer::actingAs($user)
            ->tool(ListTasksTool::class, ['overdue' => true])
            ->assertOk()
            ->assertSee('Late task')
            ->assertDontSee('Fresh task');
    }

    public function test_completing_a_task_stamps_the_completion_time(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for(Project::factory()->for($user))->create();

        TaskFlowServer::actingAs($user)
            ->tool(CompleteTaskTool::class, ['task_id' => $task->id])
            ->assertOk()
            ->assertSee('Completed task');

        $task->refresh();

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_a_task_can_be_reopened(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->done()->for(Project::factory()->for($user))->create();

        TaskFlowServer::actingAs($user)
            ->tool(CompleteTaskTool::class, ['task_id' => $task->id, 'completed' => false])
            ->assertOk()
            ->assertSee('Reopened task');

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_another_users_task_is_reported_as_missing(): void
    {
        $user = User::factory()->create();
        $foreign = Task::factory()->for(Project::factory()->for(User::factory()))->create();

        TaskFlowServer::actingAs($user)
            ->tool(DeleteTaskTool::class, ['task_id' => $foreign->id])
            ->assertHasErrors()
            ->assertSee('was not found');

        $this->assertDatabaseHas('tasks', ['id' => $foreign->id]);
    }

    public function test_the_progress_report_streams_one_notification_per_project(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->for($user)->create();

        TaskFlowServer::actingAs($user)
            ->tool(GenerateProgressReportTool::class)
            ->assertOk()
            ->assertNotificationCount(3)
            ->assertSentNotification('processing/progress', [
                'current' => 1,
                'total' => 3,
                'project' => $projects->sortBy('name')->first()->name,
            ])
            ->assertSee('Progress report');
    }
}
