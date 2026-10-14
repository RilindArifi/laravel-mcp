<?php

namespace App\Mcp\Resources;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Laravel\Mcp\Enums\CacheScope;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Cacheable;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('conventions')]
#[Title('TaskFlow conventions')]
#[Description('What the statuses and priorities mean, and which tool to reach for. Read this before changing data.')]
#[Uri('taskflow://docs/conventions')]
#[MimeType('text/markdown')]
#[Cacheable(ttlMs: 3_600_000, scope: CacheScope::Public)]
class TaskFlowConventionsResource extends Resource
{
    /**
     * Handle the resource request.
     *
     * This resource contains no user data, which is why it is the only
     * primitive in this server that may be cached publicly.
     */
    public function handle(Request $request): Response
    {
        $taskStatuses = implode("\n", array_map(
            fn (TaskStatus $status): string => "- `{$status->value}` — {$status->label()}",
            TaskStatus::cases(),
        ));

        $priorities = implode("\n", array_map(
            fn (TaskPriority $priority): string => "- `{$priority->value}` — {$priority->label()}",
            TaskPriority::cases(),
        ));

        $projectStatuses = implode("\n", array_map(
            fn (ProjectStatus $status): string => "- `{$status->value}` — {$status->label()}",
            ProjectStatus::cases(),
        ));

        return Response::text(<<<MARKDOWN
        # TaskFlow conventions

        ## Project statuses
        {$projectStatuses}

        ## Task statuses
        {$taskStatuses}

        A task is **overdue** when it has a due date in the past and its status is
        anything other than `done`.

        ## Task priorities
        {$priorities}

        ## Which tool to use
        - Start with `list-projects`; every task tool needs a project or task id.
        - Use `complete-task` to tick a task off or reopen it; it keeps the
          completion timestamp that feeds "completed this week".
        - Use `update-task` for anything else (title, description, priority, due date).
        - `delete-project` also deletes the project's tasks. There is no undo.

        ## Scoping
        Every tool and resource is scoped to the authenticated user. Ids that
        belong to someone else are reported as "not found", never as forbidden.
        MARKDOWN);
    }
}
