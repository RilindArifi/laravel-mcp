<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class McpWebRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_http_server_rejects_anonymous_calls(): void
    {
        $this->postJson('/mcp/taskflow', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'server/discover',
        ])->assertUnauthorized();
    }

    public function test_the_http_server_is_guarded_by_sanctum_and_throttled(): void
    {
        $route = Route::getRoutes()->getByName('mcp.taskflow');

        $this->assertNotNull($route);
        $this->assertContains('auth:sanctum', $route->gatherMiddleware());
        $this->assertContains('throttle:mcp', $route->gatherMiddleware());
    }

    public function test_a_valid_token_reaches_the_server(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test client', ['mcp:read', 'mcp:write'])->plainTextToken;

        $meta = [
            'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
            'io.modelcontextprotocol/clientCapabilities' => (object) [],
        ];

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'server/discover',
        ])->postJson('/mcp/taskflow', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'server/discover',
            'params' => ['_meta' => $meta],
        ])
            ->assertOk()
            ->assertJsonPath('result.supportedVersions', ['2026-07-28'])
            ->assertSee('TaskFlow is a project and task tracker');
    }
}
