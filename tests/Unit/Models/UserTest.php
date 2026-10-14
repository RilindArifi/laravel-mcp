<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_reaches_through_projects_and_excludes_other_users(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->count(3)->for($project)->create();

        Task::factory()->count(2)->for(Project::factory()->for(User::factory()))->create();

        $this->assertSame(3, $user->tasks()->count());
    }

    public function test_scopes_still_work_through_the_has_many_through_join(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        Task::factory()->for($project)->overdue()->create();
        Task::factory()->for($project)->done()->create();
        Task::factory()->for($project)->create();

        $this->assertSame(1, $user->tasks()->overdue()->count());
        $this->assertSame(2, $user->tasks()->open()->count());
    }
}
