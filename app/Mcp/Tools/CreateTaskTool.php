<?php

namespace App\Mcp\Tools;

use App\Concerns\TaskValidationRules;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Models\Task;
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

#[Name('create-task')]
#[Title('Create task')]
#[Description('Add a task to one of the projects. Labels are created on demand.')]
class CreateTaskTool extends Tool
{
    use InteractsWithTaskFlowUser, TaskValidationRules;

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
            'project_id' => $this->ownedProjectRules($user->id),
            ...$this->taskRules(),
        ], [
            'project_id.exists' => 'No project with that id exists in this workspace. Call list-projects first.',
        ]);

        $project = $this->findProject($user, (int) $validated['project_id']);

        if ($project === null) {
            return Response::error('No project with that id exists in this workspace. Call list-projects first.');
        }

        Gate::forUser($user)->authorize('create', [Task::class, $project]);

        $labels = $validated['labels'] ?? [];

        unset($validated['project_id'], $validated['labels']);

        $task = $project->tasks()->create($validated);

        $this->attachLabels($task, $labels);

        // The database fills in the status and priority defaults, so read the
        // row back before presenting it.
        $task->refresh()->load('labels');

        return Response::make(Response::text("Created task [{$task->id}] {$task->title} in {$project->name}."))
            ->withStructuredContent(['task' => TaskFlowPayload::task($task)]);
    }

    /**
     * Attach the given label names to the task, creating them for the user when needed.
     *
     * @param  array<int, string>  $names
     */
    protected function attachLabels(Task $task, array $names): void
    {
        if ($names === []) {
            return;
        }

        $user = $task->project->user;

        $ids = array_map(
            fn (string $name): int => $user->labels()->firstOrCreate(['name' => $name])->id,
            $names,
        );

        $task->labels()->sync($ids);
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
                ->description('The project to add the task to, from list-projects.')
                ->required(),
            'title' => $schema->string()
                ->description('What needs to be done.')
                ->required(),
            'description' => $schema->string()
                ->description('Any extra detail about the task.'),
            'status' => $schema->string()
                ->enum(TaskStatus::class)
                ->description('The starting status.')
                ->default(TaskStatus::Todo->value),
            'priority' => $schema->string()
                ->enum(TaskPriority::class)
                ->description('How urgent the task is.')
                ->default(TaskPriority::Medium->value),
            'due_date' => $schema->string()
                ->description('The due date, as YYYY-MM-DD.'),
            'labels' => $schema->array()
                ->items($schema->string())
                ->description('Label names to attach. Unknown labels are created.')
                ->max(5),
        ];
    }
}
