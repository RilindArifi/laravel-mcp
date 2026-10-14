<?php

namespace App\Mcp\Resources;

use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Models\Project;
use App\Support\TaskFlowPayload;
use App\Support\WorkspaceStats;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('workspace-overview')]
#[Title('Workspace overview')]
#[Description('The same counters the dashboard shows: active projects, open tasks, overdue tasks and tasks completed this week, plus a per-project breakdown.')]
#[Uri('taskflow://workspace/overview')]
#[MimeType('application/json')]
#[Audience(Role::Assistant)]
#[Priority(0.9)]
class WorkspaceOverviewResource extends Resource
{
    use InteractsWithTaskFlowUser;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        return Response::json([
            'stats' => WorkspaceStats::for($user),
            'status_breakdown' => WorkspaceStats::statusBreakdown($user),
            'projects' => $user->projects()
                ->withTaskCounts()
                ->orderBy('name')
                ->get()
                ->map(fn (Project $project): array => TaskFlowPayload::project($project))
                ->all(),
        ]);
    }
}
