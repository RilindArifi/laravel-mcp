<?php

namespace App\Mcp\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Models\Task;
use App\Support\TaskFlowPayload;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-tasks')]
#[Title('List tasks')]
#[Description('List tasks across the workspace. Supports filtering by project, status, priority, overdue state, due window and free text.')]
#[IsReadOnly]
class ListTasksTool extends Tool
{
    use InteractsWithTaskFlowUser;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'project_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'overdue' => ['nullable', 'boolean'],
            'due_within_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'search' => ['nullable', 'string', 'max:60'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'status.*' => 'Use one of: '.implode(', ', TaskStatus::values()).'.',
            'priority.*' => 'Use one of: '.implode(', ', TaskPriority::values()).'.',
        ]);

        $tasks = $user->tasks()
            ->with(['project:id,name', 'labels'])
            ->when($validated['project_id'] ?? null, fn ($query, $projectId) => $query->where('tasks.project_id', $projectId))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('tasks.status', $status))
            ->when($validated['priority'] ?? null, fn ($query, $priority) => $query->where('tasks.priority', $priority))
            ->when($validated['overdue'] ?? false, fn ($query) => $query->overdue())
            ->when(
                isset($validated['due_within_days']),
                fn ($query) => $query->whereNotNull('tasks.due_date')
                    ->whereDate('tasks.due_date', '<=', today()->addDays((int) $validated['due_within_days'])),
            )
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('tasks.title', 'like', "%{$search}%"))
            ->orderByRaw('tasks.due_date is null, tasks.due_date')
            ->limit($validated['limit'] ?? 25)
            ->get()
            ->map(fn (Task $task): array => TaskFlowPayload::task($task, withProject: true))
            ->all();

        if ($tasks === []) {
            return Response::make(Response::text('No tasks matched those filters.'))
                ->withStructuredContent(['tasks' => []]);
        }

        $lines = array_map(
            fn (array $task): string => sprintf(
                '- [%d] %s — %s, %s priority%s%s (%s)',
                $task['id'],
                $task['title'],
                $task['status_label'],
                $task['priority_label'],
                $task['due_date'] === null ? '' : ', due '.$task['due_date'],
                $task['is_overdue'] ? ' (OVERDUE)' : '',
                $task['project']['name'] ?? 'unknown project',
            ),
            $tasks,
        );

        return Response::make(Response::text(implode("\n", $lines)))
            ->withStructuredContent(['tasks' => $tasks]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()
                ->description('Only return tasks in this project, from list-projects.'),
            'status' => $schema->string()
                ->enum(TaskStatus::class)
                ->description('Only return tasks with this status.'),
            'priority' => $schema->string()
                ->enum(TaskPriority::class)
                ->description('Only return tasks with this priority.'),
            'overdue' => $schema->boolean()
                ->description('Only return unfinished tasks whose due date has passed.'),
            'due_within_days' => $schema->integer()
                ->description('Only return tasks due within this many days from today.'),
            'search' => $schema->string()
                ->description('Only return tasks whose title contains this text.'),
            'limit' => $schema->integer()
                ->description('Maximum number of tasks to return (1-100).')
                ->default(25),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'tasks' => $schema->array()
                ->description('The matching tasks.')
                ->required(),
        ];
    }
}
