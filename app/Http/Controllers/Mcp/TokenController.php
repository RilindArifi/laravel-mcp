<?php

namespace App\Http\Controllers\Mcp;

use App\Concerns\McpTokenValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mcp\TokenStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Token;
use Symfony\Component\Process\PhpExecutableFinder;

class TokenController extends Controller
{
    use McpTokenValidationRules;

    /**
     * Show everything that can reach the HTTP MCP server as this user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('mcp-tokens/Index', [
            'tokens' => $user->tokens()
                ->where('revoked', false)
                ->with('client')
                ->latest()
                ->get()
                ->map(fn (Token $token): array => [
                    'id' => $token->id,
                    'name' => $token->name ?: $token->client->name,
                    // An OAuth client was authorized in the browser; a personal
                    // token was issued from this page or from the console.
                    'kind' => $token->client->hasGrantType('personal_access') ? 'personal' : 'oauth',
                    'scopes' => $token->scopes,
                    'expires_at' => $token->expires_at?->toDateString(),
                    'created_at' => $token->created_at?->toDateString(),
                ])
                ->all(),
            'scopeOptions' => $this->mcpScopes(),
            'endpoint' => route('mcp.taskflow'),
            // Everything the connection snippets need, resolved server side so
            // the user can paste them without editing paths by hand.
            'phpBinary' => $this->phpBinary(),
            'artisanPath' => base_path('artisan'),
            'userEmail' => $user->email,
            // Shown once, immediately after the token is issued.
            'newToken' => $request->session()->get('mcpToken'),
        ]);
    }

    /**
     * Get the absolute path of the PHP CLI binary, for the stdio snippet.
     *
     * GUI clients do not inherit the shell PATH, so a bare "php" would not
     * resolve for them.
     */
    protected function phpBinary(): string
    {
        $finder = (new PhpExecutableFinder)->find();

        return is_string($finder) && ! str_contains($finder, 'fpm') ? $finder : 'php';
    }

    /**
     * Issue a new personal access token for the MCP server.
     */
    public function store(TokenStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $result = $request->user()->createToken($validated['name'], $validated['scopes']);

        $token = $result->getToken();

        if (($validated['expires_in_days'] ?? null) !== null && $token !== null) {
            $token->forceFill([
                'expires_at' => now()->addDays((int) $validated['expires_in_days']),
            ])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Token issued.')]);

        return to_route('mcp-tokens.index')->with('mcpToken', $result->accessToken);
    }

    /**
     * Revoke a token, whether it was issued here or granted through OAuth.
     */
    public function destroy(Request $request, Token $token): RedirectResponse
    {
        abort_unless($token->user_id === $request->user()->getAuthIdentifier(), 403);

        $token->revoke();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Access revoked.')]);

        return to_route('mcp-tokens.index');
    }
}
