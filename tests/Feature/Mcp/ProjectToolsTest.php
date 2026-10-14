<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TaskFlowServer;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\UpdateProjectTool;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_projects_never_leaks_another_users_work(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->create(['name' => 'Website Redesign']);
        Project::factory()->for(User::factory())->create(['name' => 'Analytical Engine']);

        TaskFlowServer::actingAs($user)
            ->tool(ListProjectsTool::class)
            ->assertOk()
            ->assertName('list-projects')
            ->assertSee('Website Redesign')
            ->assertDontSee('Analytical Engine');
    }

    public function test_creating_a_project_assigns_it_to_the_acting_user(): void
    {
        $user = User::factory()->create();

        TaskFlowServer::actingAs($user)
            ->tool(CreateProjectTool::class, ['name' => 'Mobile App v2'])
            ->assertOk()
            ->assertSee('Created project');

        $this->assertDatabaseHas('projects', [
            'name' => 'Mobile App v2',
            'user_id' => $user->id,
        ]);
    }

    public function test_creating_a_project_without_a_name_reports_a_usable_error(): void
    {
        TaskFlowServer::actingAs(User::factory()->create())
            ->tool(CreateProjectTool::class, [])
            ->assertHasErrors();
    }

    public function test_another_users_project_is_reported_as_missing_rather_than_forbidden(): void
    {
        $user = User::factory()->create();
        $foreign = Project::factory()->for(User::factory())->create();

        TaskFlowServer::actingAs($user)
            ->tool(UpdateProjectTool::class, ['project_id' => $foreign->id, 'name' => 'Hijacked'])
            ->assertHasErrors()
            ->assertSee('was not found');

        $this->assertNotSame('Hijacked', $foreign->fresh()->name);
    }

    public function test_deleting_a_project_reports_the_cascaded_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->count(2)->for($project)->create();

        TaskFlowServer::actingAs($user)
            ->tool(DeleteProjectTool::class, ['project_id' => $project->id])
            ->assertOk()
            ->assertSee('2 task(s)');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
