<?php

namespace App\Mcp\Tools;

use App\Concerns\ProjectValidationRules;
use App\Enums\ProjectStatus;
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

#[Name('update-project')]
#[Title('Update project')]
#[Description('Update the name, description, status or due date of a project.')]
#[IsIdempotent]
class UpdateProjectTool extends Tool
{
    use InteractsWithTaskFlowUser, ProjectValidationRules;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $this->user($request);

        if (! $this->canWrite($user)) {
            return Response::error('This MCP token is read-only.');
        }

        $projectId = (int) $request->get('project_id');
        $project = $this->findProject($user, $projectId);

        if ($project === null) {
            return Response::error("Project [{$projectId}] was not found. Call list-projects to see the available ids.");
        }

        Gate::forUser($user)->authorize('update', $project);

        $rules = $this->projectRules($user->id, $project->id);
        $rules['name'][0] = 'sometimes';
        $rules['project_id'] = ['required', 'integer'];

        $validated = $request->validate($rules);

        unset($validated['project_id']);

        $project->update($validated);

        return Response::make(Response::text("Updated project [{$project->id}] {$project->name}."))
            ->withStructuredContent(['project' => TaskFlowPayload::project($project->refresh())]);
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
                ->description('The id of the project to update, from list-projects.')
                ->required(),
            'name' => $schema->string()->description('A new project name.'),
            'description' => $schema->string()->description('A new description.'),
            'status' => $schema->string()
                ->enum(ProjectStatus::class)
                ->description('A new status.'),
            'due_date' => $schema->string()->description('A new due date, as YYYY-MM-DD.'),
        ];
    }
}
