<?php

namespace App\Mcp\Tools;

use App\Enums\TaskStatus;
use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Support\TaskFlowPayload;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('complete-task')]
#[Title('Complete task')]
#[Description('Mark a task as done, or reopen it. Completing a task stamps the completion time used by the weekly stats.')]
#[IsIdempotent]
class CompleteTaskTool extends Tool
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
            'completed' => ['nullable', 'boolean'],
        ]);

        $taskId = (int) $validated['task_id'];
        $task = $this->findTask($user, $taskId);

        if ($task === null) {
            return Response::error("Task [{$taskId}] was not found. Call list-tasks to see the available ids.");
        }

        Gate::forUser($user)->authorize('update', $task);

        $completed = $validated['completed'] ?? true;

        $task->moveTo($completed ? TaskStatus::Done : TaskStatus::Todo);

        $verb = $completed ? 'Completed' : 'Reopened';

        return Response::make(Response::text("{$verb} task [{$task->id}] {$task->title}."))
            ->withStructuredContent(['task' => TaskFlowPayload::task($task->refresh())]);
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
                ->description('The id of the task, from list-tasks.')
                ->required(),
            'completed' => $schema->boolean()
                ->description('True to mark the task done, false to reopen it.')
                ->default(true),
        ];
    }
}
