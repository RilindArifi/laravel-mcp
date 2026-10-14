<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local MCP User
    |--------------------------------------------------------------------------
    |
    | The stdio MCP server runs as an Artisan command, so there is no HTTP
    | request and therefore no authenticated user. The `mcp:serve` command
    | signs in the user configured here (or passed via --user) so that every
    | tool, resource and prompt sees the same scoped data the browser does.
    |
    | This is an identity selector for local development, not a security
    | boundary: anyone who can run artisan already has database access. The
    | security boundary is the HTTP transport, which uses Sanctum tokens.
    |
    */

    'mcp' => [
        'local_user' => env('TASKFLOW_MCP_USER'),
    ],

];
