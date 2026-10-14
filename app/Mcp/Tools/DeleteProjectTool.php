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

#[Name('delete-project')]
#[Title('Delete project')]
#[Description('Permanently delete a project and every task inside it.')]
#[IsDestructive]
class DeleteProjectTool extends Tool
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

        $request->validate(['project_id' => ['required', 'integer']]);

        $projectId = (int) $request->get('project_id');
        $project = $this->findProject($user, $projectId);

        if ($project === null) {
            return Response::error("Project [{$projectId}] was not found. Call list-projects to see the available ids.");
        }

        Gate::forUser($user)->authorize('delete', $project);

        $taskCount = $project->tasks()->count();
        $name = $project->name;

        $project->delete();

        return Response::text("Deleted project [{$projectId}] {$name} and its {$taskCount} task(s).");
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
                ->description('The id of the project to delete, from list-projects.')
                ->required(),
        ];
    }
}
