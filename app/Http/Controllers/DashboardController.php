<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Support\TaskFlowPayload;
use App\Support\WorkspaceStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the workspace dashboard.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'stats' => WorkspaceStats::for($user),
            'statusBreakdown' => WorkspaceStats::statusBreakdown($user),
            'dueSoon' => $user->tasks()
                ->open()
                ->whereNotNull('tasks.due_date')
                ->whereDate('tasks.due_date', '<=', today()->addDays(7))
                ->with(['project:id,name', 'labels'])
                ->orderBy('tasks.due_date')
                ->limit(6)
                ->get()
                ->map(fn (Task $task): array => TaskFlowPayload::task($task, withProject: true))
                ->all(),
            'recentProjects' => $user->projects()
                ->withTaskCounts()
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->map(fn (Project $project): array => TaskFlowPayload::project($project))
                ->all(),
        ]);
    }
}
