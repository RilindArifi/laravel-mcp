<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\PlanProjectPrompt;
use App\Mcp\Prompts\WeeklyReviewPrompt;
use App\Mcp\Resources\ProjectSummaryResource;
use App\Mcp\Resources\TaskFlowConventionsResource;
use App\Mcp\Resources\WorkspaceOverviewResource;
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
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('TaskFlow')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
TaskFlow is a project and task tracker. This server exposes the same data the
web app shows, for the one authenticated user: their projects, the tasks inside
them, and the labels attached to those tasks.

Working rules:
- Call `list-projects` first. Every task tool needs a project id or a task id,
  and ids are never guessable.
- Read `taskflow://docs/conventions` for what each status and priority means,
  and `taskflow://workspace/overview` for the current counters.
- Use `complete-task` to tick a task off or reopen it, and `update-task` for any
  other change.
- An id that belongs to someone else is reported as "not found". That is the
  authorization boundary, not a bug: everything is scoped to the current user.
MARKDOWN)]
class TaskFlowServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
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
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        WorkspaceOverviewResource::class,
        ProjectSummaryResource::class,
        TaskFlowConventionsResource::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        WeeklyReviewPrompt::class,
        PlanProjectPrompt::class,
    ];
}
