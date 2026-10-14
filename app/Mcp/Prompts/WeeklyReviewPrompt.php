<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('weekly-review')]
#[Title('Weekly review')]
#[Description('Run a weekly review of the workspace: what shipped, what slipped, and what to do next.')]
class WeeklyReviewPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     *
     * @return array<int, Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'tone' => ['nullable', 'string', 'max:40'],
        ]);

        $tone = $validated['tone'] ?? 'concise and factual';

        return [
            Response::text(<<<MARKDOWN
            You are reviewing a TaskFlow workspace through its MCP server.

            Work in this order:
            1. Read `taskflow://workspace/overview` for the headline numbers.
            2. Call `list-tasks` with `overdue: true` to see what slipped.
            3. Call `list-tasks` with `status: done` to see what was finished.
            4. Call `list-tasks` with `due_within_days: 7` for what is coming up.

            Then write the review in a {$tone} tone, with three sections:
            **Shipped**, **Slipped** and **Next week**. Reference tasks as
            "[id] title" so they can be acted on, and end with at most three
            concrete suggestions. Do not change any data.
            MARKDOWN)->asAssistant(),
            Response::text('Give me my weekly review.'),
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
                name: 'tone',
                description: 'The tone of the review, for example "concise and factual" or "encouraging".',
                required: false,
            ),
        ];
    }
}
