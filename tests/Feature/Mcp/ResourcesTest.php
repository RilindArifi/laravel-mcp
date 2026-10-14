<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Resources\ProjectSummaryResource;
use App\Mcp\Resources\TaskFlowConventionsResource;
use App\Mcp\Resources\WorkspaceOverviewResource;
use App\Mcp\Servers\TaskFlowServer;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_workspace_overview_reports_the_dashboard_numbers(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->count(2)->for($project)->overdue()->create();
        Task::factory()->for($project)->done()->create();

        Project::factory()->for(User::factory())->create(['name' => 'Analytical Engine']);

        TaskFlowServer::actingAs($user)
            ->resource(WorkspaceOverviewResource::class)
            ->assertOk()
            ->assertSee('"overdue_tasks":2')
            ->assertDontSee('Analytical Engine');
    }

    public function test_the_project_summary_template_renders_markdown_for_an_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Website Redesign']);
        Task::factory()->for($project)->overdue()->create(['title' => 'Migrate the blog content']);

        TaskFlowServer::actingAs($user)
            ->resource(ProjectSummaryResource::class, ['projectId' => $project->id])
            ->assertOk()
            ->assertSee('# Website Redesign')
            ->assertSee('Migrate the blog content')
            ->assertSee('(overdue)');
    }

    public function test_the_project_summary_template_refuses_another_users_project(): void
    {
        $user = User::factory()->create();
        $foreign = Project::factory()->for(User::factory())->create();

        TaskFlowServer::actingAs($user)
            ->resource(ProjectSummaryResource::class, ['projectId' => $foreign->id])
            ->assertHasErrors()
            ->assertSee('was not found');
    }

    public function test_the_conventions_resource_documents_every_status(): void
    {
        TaskFlowServer::actingAs(User::factory()->create())
            ->resource(TaskFlowConventionsResource::class)
            ->assertOk()
            ->assertSee(['`todo`', '`in_progress`', '`blocked`', '`done`', 'overdue']);
    }
}
