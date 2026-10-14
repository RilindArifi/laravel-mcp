<?php

namespace App\Support;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\User;

class WorkspaceStats
{
    /**
     * Get the headline counters for the user's workspace.
     *
     * Shared by the dashboard and the MCP workspace overview resource so both
     * surfaces always report the same numbers.
     *
     * @return array{active_projects: int, open_tasks: int, overdue_tasks: int, completed_this_week: int}
     */
    public static function for(User $user): array
    {
        return [
            'active_projects' => $user->projects()->where('status', ProjectStatus::Active)->count(),
            'open_tasks' => $user->tasks()->open()->count(),
            'overdue_tasks' => $user->tasks()->overdue()->count(),
            'completed_this_week' => $user->tasks()
                ->where('tasks.status', TaskStatus::Done)
                ->where('tasks.completed_at', '>=', now()->startOfWeek())
                ->count(),
        ];
    }

    /**
     * Get the number of tasks per status.
     *
     * @return array<string, int>
     */
    public static function statusBreakdown(User $user): array
    {
        /** @var array<string, int> $counts */
        $counts = $user->tasks()
            ->selectRaw('tasks.status as status, count(*) as aggregate')
            ->groupBy('tasks.status')
            ->pluck('aggregate', 'status')
            ->all();

        $breakdown = [];

        foreach (TaskStatus::cases() as $status) {
            $breakdown[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $breakdown;
    }
}
