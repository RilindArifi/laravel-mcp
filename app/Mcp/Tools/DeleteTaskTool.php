<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('delete-task')]
#[Title('Delete task')]
#[Description('Permanently delete a task.')]
#[IsDestructive]
class DeleteTaskTool extends Tool
{
    use InteractsWithTaskFlowUser;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $this->user($request);

        if (! $this->canWrite($user)) {
            return Response::error('This MCP token is read-only.');
        }

        $request->validate(['task_id' => ['required', 'integer']]);

        $taskId = (int) $request->get('task_id');
        $task = $this->findTask($user, $taskId);

        if ($task === null) {
            return Response::error("Task [{$taskId}] was not found. Call list-tasks to see the available ids.");
        }

        Gate::forUser($user)->authorize('delete', $task);

        $title = $task->title;

        $task->delete();

        return Response::text("Deleted task [{$taskId}] {$title}.");
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
                ->description('The id of the task to delete, from list-tasks.')
                ->required(),
        ];
    }
}
