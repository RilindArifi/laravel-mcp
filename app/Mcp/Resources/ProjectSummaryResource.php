<?php

namespace App\Mcp\Resources;

use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Models\Task;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('project-summary')]
#[Title('Project summary')]
#[Description('A markdown summary of one project: progress, tasks grouped by status, and anything overdue. Read it at taskflow://projects/{projectId}/summary.')]
#[MimeType('text/markdown')]
class ProjectSummaryResource extends Resource implements HasUriTemplate
{
    use InteractsWithTaskFlowUser;

    /**
     * Get the URI template this resource answers.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('taskflow://projects/{projectId}/summary');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        $projectId = (int) $request->get('projectId');
        $project = $this->findProject($user, $projectId);

        if ($project === null) {
            return Response::error("Project [{$projectId}] was not found in this workspace.");
        }

        $project->loadMissing('tasks.labels');

        $lines = [
            "# {$project->name}",
            '',
            "- Status: {$project->status->label()}",
            '- Progress: '.$project->progress().'%',
        ];

        if ($project->due_date !== null) {
            $lines[] = '- Due: '.$project->due_date->toDateString();
        }

        if ($project->description !== null) {
            $lines[] = '';
            $lines[] = $project->description;
        }

        foreach ($project->tasks->groupBy(fn (Task $task): string => $task->status->value) as $status => $tasks) {
            $lines[] = '';
            $lines[] = '## '.$tasks->first()->status->label().' ('.$tasks->count().')';

            foreach ($tasks as $task) {
                $lines[] = sprintf(
                    '- [%d] %s — %s priority%s%s',
                    $task->id,
                    $task->title,
                    $task->priority->label(),
                    $task->due_date === null ? '' : ', due '.$task->due_date->toDateString(),
                    $task->isOverdue() ? ' **(overdue)**' : '',
                );
            }
        }

        return Response::text(implode("\n", $lines));
    }
}
