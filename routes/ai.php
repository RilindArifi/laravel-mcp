<?php

use App\Mcp\Servers\TaskFlowServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Servers
|--------------------------------------------------------------------------
|
| The same server class is exposed twice. Locally it runs over stdio for
| editor agents (see the `mcp:serve` command, which selects the user), and
| over HTTP for remote clients, which authenticate with Passport: either by
| walking the OAuth 2.1 flow, or with a personal access token issued from the
| MCP tokens page.
|
*/

Mcp::local('taskflow', TaskFlowServer::class);

Mcp::oauthRoutes();

Mcp::web('/mcp/taskflow', TaskFlowServer::class)
    ->middleware(['auth:api', 'throttle:mcp'])
    ->name('mcp.taskflow');
