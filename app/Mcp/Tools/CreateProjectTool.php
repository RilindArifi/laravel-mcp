<?php

namespace App\Mcp\Tools;

use App\Concerns\ProjectValidationRules;
use App\Enums\ProjectStatus;
use App\Mcp\Concerns\InteractsWithTaskFlowUser;
use App\Support\TaskFlowPayload;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('create-project')]
#[Title('Create project')]
#[Description('Create a new project in the workspace. Returns the new project id, which the task tools need.')]
class CreateProjectTool extends Tool
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

        $validated = $request->validate($this->projectRules($user->id), [
            'name.unique' => 'You already have a project with that name. Pick another name, or update the existing project.',
        ]);

        $project = $user->projects()->create($validated);

        // The database fills in the status default, so read the row back
        // before presenting it.
        $project->refresh();

        return Response::make(Response::text("Created project [{$project->id}] {$project->name}."))
            ->withStructuredContent(['project' => TaskFlowPayload::project($project)]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The project name. Must be unique within the workspace.')
                ->required(),
            'description' => $schema->string()
                ->description('What the project is about.'),
            'status' => $schema->string()
                ->enum(ProjectStatus::class)
                ->description('The project status.')
                ->default(ProjectStatus::Active->value),
            'due_date' => $schema->string()
                ->description('The project due date, as YYYY-MM-DD.'),
        ];
    }
}
