<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_the_dashboard_counts_only_the_users_own_work(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->count(2)->for($project)->overdue()->create();
        Task::factory()->for($project)->done()->create(['completed_at' => now()]);
        Task::factory()->for($project)->create();

        Task::factory()->count(5)->for(Project::factory()->for(User::factory()))->overdue()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('stats.active_projects', 1)
                ->where('stats.open_tasks', 3)
                ->where('stats.overdue_tasks', 2)
                ->where('stats.completed_this_week', 1)
                ->has('dueSoon')
                ->has('recentProjects', 1));
    }
}
