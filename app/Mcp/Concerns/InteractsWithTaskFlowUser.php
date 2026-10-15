<?php

namespace App\Mcp\Concerns;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Mcp\Request;
use RuntimeException;

/**
 * Resolves and scopes the user behind an MCP call.
 *
 * Every tool, resource and prompt goes through these helpers, so the HTTP
 * transport (Passport: OAuth or a personal access token) and the stdio
 * transport (`php artisan mcp:serve`)
 * are subject to exactly the same scoping as the browser.
 */
trait InteractsWithTaskFlowUser
{
    /**
     * Get the user this request acts on behalf of.
     */
    protected function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new RuntimeException(
                'This MCP server requires an authenticated user. Authorize through OAuth or send a personal access token for the HTTP server, or run `php artisan mcp:serve --user=you@example.com` locally.',
            );
        }

        return $user;
    }

    /**
     * Find one of the user's projects, or null when it does not exist.
     *
     * Another user's project is indistinguishable from a missing one, so the
     * tools cannot be used to probe for rows that are not yours.
     */
    protected function findProject(User $user, int $id): ?Project
    {
        return $user->projects()->find($id);
    }

    /**
     * Find one of the user's tasks, or null when it does not exist.
     */
    protected function findTask(User $user, int $id): ?Task
    {
        return $user->tasks()->with('project')->find($id);
    }

    /**
     * Determine whether the caller is allowed to change data.
     *
     * Personal access tokens may be minted read-only; the stdio transport has no token
     * and is always allowed to write.
     */
    protected function canWrite(User $user): bool
    {
        $token = $user->token();

        // Only the HTTP transport carries a token. The stdio transport has
        // none, and anyone who can run artisan already has database access.
        if ($token === null) {
            return true;
        }

        // The OAuth flow grants `mcp:use`, which is full access; personal
        // access tokens can be narrowed to `mcp:read`.
        return $token->can('mcp:write') || $token->can('mcp:use');
    }
}
