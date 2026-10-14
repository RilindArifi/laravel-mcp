<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_only_lists_the_users_own_projects(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->create(['name' => 'Mine']);
        Project::factory()->for(User::factory())->create(['name' => 'Someone elses']);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('projects/Index')
                ->has('projects.data', 1)
                ->where('projects.data.0.name', 'Mine'));
    }

    public function test_a_project_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Website Redesign',
            'description' => 'Rebuild the marketing site.',
            'status' => ProjectStatus::Active->value,
        ]);

        $project = Project::firstWhere('name', 'Website Redesign');

        $this->assertNotNull($project);
        $this->assertSame($user->id, $project->user_id);
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_project_names_are_unique_per_user_but_not_globally(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->create(['name' => 'Website Redesign']);
        Project::factory()->for(User::factory())->create(['name' => 'Taken elsewhere']);

        $this->actingAs($user)
            ->post(route('projects.store'), ['name' => 'Website Redesign'])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->post(route('projects.store'), ['name' => 'Taken elsewhere'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_project_can_be_updated(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create(['name' => 'Old name']);

        $this->actingAs($user)
            ->put(route('projects.update', $project), [
                'name' => 'New name',
                'status' => ProjectStatus::OnHold->value,
            ])
            ->assertRedirect(route('projects.show', $project));

        $project->refresh();

        $this->assertSame('New name', $project->name);
        $this->assertSame(ProjectStatus::OnHold, $project->status);
    }

    public function test_deleting_a_project_deletes_its_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->count(2)->for($project)->create();

        $this->actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertSame(0, Task::where('project_id', $project->id)->count());
    }

    public function test_another_users_project_is_forbidden(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for(User::factory())->create();

        $this->actingAs($user)->get(route('projects.show', $project))->assertForbidden();
        $this->actingAs($user)->get(route('projects.edit', $project))->assertForbidden();
        $this->actingAs($user)->put(route('projects.update', $project), ['name' => 'Hijacked'])->assertForbidden();
        $this->actingAs($user)->delete(route('projects.destroy', $project))->assertForbidden();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }
}
