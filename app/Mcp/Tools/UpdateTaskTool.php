<?php

namespace App\Mcp\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Support\TaskFlowPayload;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update-task')]
#[Title('Update task')]
#[Description('Update a task: its title, description, status, priority or due date. Use complete-task to simply tick a task off.')]
#[IsIdempotent]
class UpdateTaskTool extends Tool
{
    use InteractsWithTaskFlowUser;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->user($request);

        if (! $this->canWrite($user)) {
            return Response::error('This MCP token is read-only.');
        }

        $validated = $request->validate([
            'task_id' => ['required', 'integer'],
            'title' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
        ]);

        $taskId = (int) $validated['task_id'];
        $task = $this->findTask($user, $taskId);

        if ($task === null) {
            return Response::error("Task [{$taskId}] was not found. Call list-tasks to see the available ids.");
        }

        Gate::forUser($user)->authorize('update', $task);

        unset($validated['task_id']);

        if (isset($validated['status'])) {
            $task->moveTo(TaskStatus::from($validated['status']));

            unset($validated['status']);
        }

        if ($validated !== []) {
            $task->update($validated);
        }

        return Response::make(Response::text("Updated task [{$task->id}] {$task->title}."))
            ->withStructuredContent(['task' => TaskFlowPayload::task($task->refresh()->load('labels'))]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('The id of the task to update, from list-tasks.')
                ->required(),
            'title' => $schema->string()->description('A new title.'),
            'description' => $schema->string()->description('A new description.'),
            'status' => $schema->string()
                ->enum(TaskStatus::class)
                ->description('A new status. Moving to done stamps the completion time.'),
            'priority' => $schema->string()
                ->enum(TaskPriority::class)
                ->description('A new priority.'),
            'due_date' => $schema->string()->description('A new due date, as YYYY-MM-DD.'),
        ];
    }
}
