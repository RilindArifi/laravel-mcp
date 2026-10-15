<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createPersonalAccessClient();
    }

    public function test_it_issues_a_token_with_the_requested_scopes(): void
    {
        $user = User::factory()->create(['email' => 'demo@taskflow.test']);

        $this->artisan('mcp:token', [
            'email' => 'demo@taskflow.test',
            '--scopes' => 'mcp:read',
        ])->assertSuccessful();

        $token = $user->tokens()->firstOrFail();

        $this->assertSame(['mcp:read'], $token->scopes);
    }

    public function test_it_fails_for_an_unknown_user(): void
    {
        $this->artisan('mcp:token', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
