<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Models\Project;
use App\Support\WorkspaceStats;
use Generator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('generate-progress-report')]
#[Title('Generate progress report')]
#[Description('Walk every project and return a written progress report. Streams a progress notification per project while it works.')]
#[IsReadOnly]
class GenerateProgressReportTool extends Tool
{
    use InteractsWithTaskFlowUser;

    /**
     * Handle the tool request.
     *
     * @return Generator<int, Response>
     */
    public function handle(Request $request): Generator
    {
        $user = $this->user($request);

        $projects = $user->projects()->withTaskCounts()->orderBy('name')->get();

        if ($projects->isEmpty()) {
            yield Response::text('There are no projects to report on yet.');

            return;
        }

        $lines = [];
        $total = $projects->count();

        foreach ($projects->values() as $index => $project) {
            yield Response::notification('processing/progress', [
                'current' => $index + 1,
                'total' => $total,
                'project' => $project->name,
            ]);

            $lines[] = $this->summarize($project);
        }

        $stats = WorkspaceStats::for($user);

        $header = sprintf(
            "Progress report — %d active project(s), %d open task(s), %d overdue, %d completed this week.\n",
            $stats['active_projects'],
            $stats['open_tasks'],
            $stats['overdue_tasks'],
            $stats['completed_this_week'],
        );

        yield Response::text($header."\n".implode("\n\n", $lines));
    }

    /**
     * Summarize a single project.
     */
    protected function summarize(Project $project): string
    {
        $overdue = $project->tasks()->overdue()->count();

        return sprintf(
            "## %s (%s)\n- %d of %d tasks done (%d%%)\n- %d still open%s%s",
            $project->name,
            $project->status->label(),
            $project->completed_tasks_count ?? 0,
            $project->tasks_count ?? 0,
            $project->progress(),
            $project->open_tasks_count ?? 0,
            $overdue > 0 ? sprintf(', %d overdue', $overdue) : '',
            $project->due_date === null ? '' : sprintf("\n- Due %s", $project->due_date->toDateString()),
        );
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
