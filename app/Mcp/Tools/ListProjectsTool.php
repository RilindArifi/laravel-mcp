<?php

namespace App\Mcp\Tools;

use App\Enums\ProjectStatus;
use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Models\Project;
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

#[Name('list-projects')]
#[Title('List projects')]
#[Description('List the projects in the workspace, with task counts and completion progress. Call this first to learn the project ids the other tools need.')]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
    use InteractsWithTaskFlowUser;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $user = $this->user($request);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'search' => ['nullable', 'string', 'max:60'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ], [
            'status.*' => 'Use one of: '.implode(', ', ProjectStatus::values()).'.',
        ]);

        $projects = $user->projects()
            ->withTaskCounts()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit($validated['limit'] ?? 20)
            ->get()
            ->map(fn (Project $project): array => TaskFlowPayload::project($project))
            ->all();

        if ($projects === []) {
            return Response::make(Response::text('No projects matched. Create one with the create-project tool.'))
                ->withStructuredContent(['projects' => []]);
        }

        $lines = array_map(
            fn (array $project): string => sprintf(
                '- [%d] %s (%s) — %d/%d tasks done, %d%%%s',
                $project['id'],
                $project['name'],
                $project['status_label'],
                $project['completed_tasks_count'] ?? 0,
                $project['tasks_count'] ?? 0,
                $project['progress'],
                $project['due_date'] === null ? '' : ', due '.$project['due_date'],
            ),
            $projects,
        );

        return Response::make(Response::text(implode("\n", $lines)))
            ->withStructuredContent(['projects' => $projects]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(ProjectStatus::class)
                ->description('Only return projects with this status.'),
            'search' => $schema->string()
                ->description('Only return projects whose name contains this text.'),
            'limit' => $schema->integer()
                ->description('Maximum number of projects to return (1-50).')
                ->default(20),
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
            'projects' => $schema->array()
                ->description('The matching projects.')
                ->required(),
        ];
    }
}
