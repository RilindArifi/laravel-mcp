<?php

namespace App\Mcp\Concerns;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use RuntimeException;

/**
 * Resolves and scopes the user behind an MCP call.
 *
 * Every tool, resource and prompt goes through these helpers, so the HTTP
 * transport (Sanctum tokens) and the stdio transport (`php artisan mcp:serve`)
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
                'This MCP server requires an authenticated user. Use a Sanctum token for the HTTP server, or run `php artisan mcp:serve --user=you@example.com` locally.',
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
     * Sanctum tokens may be minted read-only; the stdio transport has no token
     * and is always allowed to write.
     */
    protected function canWrite(User $user): bool
    {
        // Only the HTTP transport carries a token, and that token may have been
        // minted read-only. The stdio transport has no token, and anyone who can
        // run artisan already has database access, so it may always write.
        if (! Auth::guard('sanctum')->check()) {
            return true;
        }

        return $user->tokenCan('mcp:write');
    }
}
