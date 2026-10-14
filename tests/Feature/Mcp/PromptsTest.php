<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Prompts\PlanProjectPrompt;
use App\Mcp\Prompts\WeeklyReviewPrompt;
use App\Mcp\Servers\TaskFlowServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_weekly_review_prompt_walks_the_agent_through_the_server(): void
    {
        TaskFlowServer::actingAs(User::factory()->create())
            ->prompt(WeeklyReviewPrompt::class, ['tone' => 'encouraging'])
            ->assertOk()
            ->assertName('weekly-review')
            ->assertSee(['taskflow://workspace/overview', 'overdue', 'encouraging']);
    }

    public function test_the_plan_project_prompt_requires_a_goal(): void
    {
        TaskFlowServer::actingAs(User::factory()->create())
            ->prompt(PlanProjectPrompt::class, [])
            ->assertHasErrors(['Describe the goal of the project, for example "ship the new pricing page".']);
    }

    public function test_the_plan_project_prompt_echoes_the_goal(): void
    {
        TaskFlowServer::actingAs(User::factory()->create())
            ->prompt(PlanProjectPrompt::class, ['goal' => 'ship the new pricing page', 'task_count' => 4])
            ->assertOk()
            ->assertSee(['ship the new pricing page', 'create-project']);
    }
}
