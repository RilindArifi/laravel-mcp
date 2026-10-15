<?php

namespace Tests\Feature\Mcp;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unauthenticated_call_points_the_client_at_the_resource_metadata(): void
    {
        $response = $this->postJson('/mcp/taskflow', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'server/discover',
            'params' => ['_meta' => $this->meta()],
        ], $this->mcpHeaders('server/discover'));

        $response->assertUnauthorized();

        $this->assertStringContainsString(
            '/.well-known/oauth-protected-resource',
            (string) $response->headers->get('WWW-Authenticate'),
        );
    }

    public function test_the_discovery_documents_describe_the_authorization_server(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonPath('scopes_supported', ['mcp:use']);

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('code_challenge_methods_supported', ['S256'])
            ->assertJsonPath('scopes_supported', ['mcp:use'])
            ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint', 'registration_endpoint']);
    }

    public function test_a_client_can_register_itself_and_complete_the_authorization_code_flow(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->create(['name' => 'Website Redesign']);

        // 1. Dynamic client registration, as an unknown client would do.
        $registration = $this->postJson('/oauth/register', [
            'client_name' => 'Test MCP Client',
            'redirect_uris' => ['https://client.test/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
        ])->assertSuccessful();

        $clientId = $registration->json('client_id');

        // 2. The user approves the consent screen.
        $verifier = str()->random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => 'https://client.test/callback',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'test-state',
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        $this->actingAs($user)->get("/oauth/authorize?{$query}")->assertOk();

        $approval = $this->actingAs($user)->post(route('passport.authorizations.approve'), [
            'state' => 'test-state',
            'client_id' => $clientId,
            'auth_token' => session('authToken'),
        ])->assertRedirect();

        parse_str(parse_url($approval->headers->get('Location'), PHP_URL_QUERY) ?? '', $callback);

        $this->assertArrayHasKey('code', $callback);
        $this->assertSame('test-state', $callback['state']);

        // 3. The code is exchanged for a token, PKCE verifier and all.
        $token = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => 'https://client.test/callback',
            'code_verifier' => $verifier,
            'code' => $callback['code'],
        ])->assertOk();

        $accessToken = $token->json('access_token');

        $this->assertNotEmpty($accessToken);
        $this->assertNotEmpty($token->json('refresh_token'));

        // 4. That token reaches the MCP server, and may write: the OAuth flow
        //    grants mcp:use, which is full access.
        $this->postJson('/mcp/taskflow', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list-projects',
                'arguments' => ['limit' => 5],
                '_meta' => $this->meta(),
            ],
        ], $this->mcpHeaders('tools/call', 'list-projects') + ['Authorization' => "Bearer {$accessToken}"])
            ->assertOk()
            ->assertSee('Website Redesign');
    }

    public function test_a_read_only_personal_token_cannot_write(): void
    {
        $this->createPersonalAccessClient();

        $user = User::factory()->create();
        $accessToken = $user->createToken('Reader', ['mcp:read'])->accessToken;

        $this->postJson('/mcp/taskflow', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'create-project',
                'arguments' => ['name' => 'Should be refused'],
                '_meta' => $this->meta(),
            ],
        ], $this->mcpHeaders('tools/call', 'create-project') + ['Authorization' => "Bearer {$accessToken}"])
            ->assertOk()
            ->assertSee('read-only');

        $this->assertDatabaseMissing('projects', ['name' => 'Should be refused']);
    }

    /**
     * Get the protocol metadata every modern MCP request carries.
     *
     * @return array<string, mixed>
     */
    protected function meta(): array
    {
        return [
            'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
            'io.modelcontextprotocol/clientCapabilities' => (object) [],
        ];
    }

    /**
     * Get the headers the MCP HTTP transport requires.
     *
     * @return array<string, string>
     */
    protected function mcpHeaders(string $method, ?string $name = null): array
    {
        return array_filter([
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => $method,
            'Mcp-Name' => $name,
        ]);
    }
}
