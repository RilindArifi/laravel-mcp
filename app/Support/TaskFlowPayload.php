<?php

namespace App\Support;

use App\Models\Label;
use App\Models\Project;
use App\Models\Task;

class TaskFlowPayload
{
    /**
     * Present a project for the UI and for MCP structured content.
     *
     * @return array<string, mixed>
     */
    public static function project(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status->value,
            'status_label' => $project->status->label(),
            'due_date' => $project->due_date?->toDateString(),
            'tasks_count' => $project->tasks_count,
            'open_tasks_count' => $project->open_tasks_count,
            'completed_tasks_count' => $project->completed_tasks_count,
            'progress' => $project->progress(),
            'updated_at' => $project->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Present a task for the UI and for MCP structured content.
     *
     * @return array<string, mixed>
     */
    public static function task(Task $task, bool $withProject = false): array
    {
        $payload = [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'status_label' => $task->status->label(),
            'priority' => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'due_date' => $task->due_date?->toDateString(),
            'is_overdue' => $task->isOverdue(),
            'completed_at' => $task->completed_at?->toDateTimeString(),
            'labels' => $task->relationLoaded('labels')
                ? $task->labels->map(fn (Label $label): array => self::label($label))->all()
                : [],
        ];

        if ($withProject && $task->relationLoaded('project')) {
            $payload['project'] = [
                'id' => $task->project->id,
                'name' => $task->project->name,
            ];
        }

        return $payload;
    }

    /**
     * Present a label.
     *
     * @return array<string, mixed>
     */
    public static function label(Label $label): array
    {
        return [
            'id' => $label->id,
            'name' => $label->name,
            'color' => $label->color,
        ];
    }
}
