<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TokenPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_users_own_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('Mine', ['mcp:read']);
        User::factory()->create()->createToken('Someone elses', ['mcp:read']);

        $this->actingAs($user)
            ->get(route('mcp-tokens.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('mcp-tokens/Index')
                ->has('tokens', 1)
                ->where('tokens.0.name', 'Mine')
                ->where('tokens.0.abilities', ['mcp:read'])
                ->where('newToken', null));
    }

    public function test_a_token_is_issued_and_shown_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('mcp-tokens.store'), [
                'name' => 'Claude Code',
                'abilities' => ['mcp:read', 'mcp:write'],
            ])
            ->assertRedirect(route('mcp-tokens.index'))
            ->assertSessionHas('mcpToken');

        $token = $user->tokens()->firstOrFail();

        $this->assertSame('Claude Code', $token->name);
        $this->assertSame(['mcp:read', 'mcp:write'], $token->abilities);
        $this->assertNull($token->expires_at);

        // The plain text token survives exactly one request: the redirect's
        // GET shows it, the next one does not.
        $this->actingAs($user)
            ->get(route('mcp-tokens.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->whereNot('newToken', null));

        $this->actingAs($user)
            ->get(route('mcp-tokens.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('newToken', null));
    }

    public function test_an_expiry_can_be_requested(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('mcp-tokens.store'), [
            'name' => 'Short lived',
            'abilities' => ['mcp:read'],
            'expires_in_days' => 7,
        ]);

        $this->assertTrue(
            $user->tokens()->firstOrFail()->expires_at->isSameDay(now()->addDays(7)),
        );
    }

    public function test_a_token_needs_a_name_and_at_least_one_ability(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('mcp-tokens.store'), ['name' => '', 'abilities' => []])
            ->assertSessionHasErrors(['name', 'abilities']);

        $this->actingAs($user)
            ->post(route('mcp-tokens.store'), ['name' => 'Bad ability', 'abilities' => ['mcp:admin']])
            ->assertSessionHasErrors('abilities.0');
    }

    public function test_a_token_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Claude Code', ['mcp:read'])->accessToken;

        $this->actingAs($user)
            ->delete(route('mcp-tokens.destroy', $token))
            ->assertRedirect(route('mcp-tokens.index'));

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_another_users_token_cannot_be_revoked(): void
    {
        $user = User::factory()->create();
        $foreign = User::factory()->create()->createToken('Theirs', ['mcp:read'])->accessToken;

        $this->actingAs($user)
            ->delete(route('mcp-tokens.destroy', $foreign))
            ->assertForbidden();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreign->id]);
    }

    public function test_guests_cannot_see_the_page(): void
    {
        $this->get(route('mcp-tokens.index'))->assertRedirect(route('login'));
    }
}
