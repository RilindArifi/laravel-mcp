<?php

namespace App\Mcp\Support;

use App\Models\User;
use InvalidArgumentException;

/**
 * Chooses the user the stdio MCP server acts as.
 *
 * This is an identity selector for local development, not a security boundary:
 * anyone able to run `artisan` already has database access. The boundary that
 * matters is the HTTP transport, which authenticates with Sanctum tokens.
 */
class LocalUserResolver
{
    /**
     * Resolve the user for the given email, falling back to the configured one.
     */
    public function resolve(?string $email = null): User
    {
        if (app()->isProduction()) {
            throw new InvalidArgumentException(
                'The stdio MCP server is disabled in production. Use the HTTP server at /mcp/taskflow with a Sanctum token instead.',
            );
        }

        $email ??= config('taskflow.mcp.local_user');

        if (! is_string($email) || $email === '') {
            throw new InvalidArgumentException(
                'No MCP user configured. Pass --user=you@example.com, or set TASKFLOW_MCP_USER in your .env file.',
            );
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw new InvalidArgumentException("No user found with the email [{$email}].");
        }

        return $user;
    }
}
