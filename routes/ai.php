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
| over HTTP for remote clients, where Sanctum tokens identify the user and
| their abilities decide whether the agent may write.
|
*/

Mcp::local('taskflow', TaskFlowServer::class);

Mcp::web('/mcp/taskflow', TaskFlowServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp'])
    ->name('mcp.taskflow');
