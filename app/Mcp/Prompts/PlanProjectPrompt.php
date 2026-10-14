<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('plan-project')]
#[Title('Plan a project')]
#[Description('Turn a goal into a new project with a first set of well-scoped tasks.')]
class PlanProjectPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'goal' => ['required', 'string', 'max:200'],
            'task_count' => ['nullable', 'integer', 'min:3', 'max:12'],
        ], [
            'goal.required' => 'Describe the goal of the project, for example "ship the new pricing page".',
        ]);

        $goal = $validated['goal'];
        $count = $validated['task_count'] ?? 5;

        return [
            Response::text(<<<MARKDOWN
            You are planning work inside a TaskFlow workspace through its MCP server.

            First read `taskflow://docs/conventions` so you use the right statuses
            and priorities, and call `list-projects` to avoid duplicating an
            existing project.

            Then create one project for the goal with `create-project`, and add
            {$count} tasks with `create-task`. Each task must be a single
            deliverable a person could finish in a day, with a priority that
            reflects its order, and a due date only where one is implied.

            Finish by listing what you created as "[id] title".
            MARKDOWN)->asAssistant(),
            Response::text("Plan this for me: {$goal}"),
        ];
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'goal',
                description: 'The outcome the project should deliver.',
                required: true,
            ),
            new Argument(
                name: 'task_count',
                description: 'How many tasks to create (3-12, default 5).',
                required: false,
            ),
        ];
    }
}
