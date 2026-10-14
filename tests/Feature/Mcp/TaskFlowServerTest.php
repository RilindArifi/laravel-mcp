<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Prompts\PlanProjectPrompt;
use App\Mcp\Prompts\WeeklyReviewPrompt;
use App\Mcp\Resources\ProjectSummaryResource;
use App\Mcp\Resources\TaskFlowConventionsResource;
use App\Mcp\Resources\WorkspaceOverviewResource;
use App\Mcp\Servers\TaskFlowServer;
use App\Mcp\Tools\CompleteTaskTool;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\DeleteTaskTool;
use App\Mcp\Tools\GenerateProgressReportTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTasksTool;
use App\Mcp\Tools\UpdateProjectTool;
use App\Mcp\Tools\UpdateTaskTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFlowServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_tool_is_registered(): void
    {
        TaskFlowServer::tools()->assertRegistered([
            ListProjectsTool::class,
            CreateProjectTool::class,
            UpdateProjectTool::class,
            DeleteProjectTool::class,
            ListTasksTool::class,
            CreateTaskTool::class,
            UpdateTaskTool::class,
            CompleteTaskTool::class,
            DeleteTaskTool::class,
            GenerateProgressReportTool::class,
        ]);
    }

    public function test_every_resource_and_prompt_is_registered(): void
    {
        // ProjectSummaryResource answers a URI template, so it is advertised
        // under resource templates rather than in the resource list. Reading it
        // is covered by ResourcesTest.
        TaskFlowServer::resources()->assertRegistered([
            WorkspaceOverviewResource::class,
            TaskFlowConventionsResource::class,
        ]);

        TaskFlowServer::resources()->assertNotRegistered(ProjectSummaryResource::class);

        TaskFlowServer::prompts()->assertRegistered([
            WeeklyReviewPrompt::class,
            PlanProjectPrompt::class,
        ]);
    }
}
