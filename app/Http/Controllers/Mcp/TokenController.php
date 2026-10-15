<?php

namespace App\Http\Controllers\Mcp;

use App\Concerns\McpTokenValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mcp\TokenStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Process\PhpExecutableFinder;

class TokenController extends Controller
{
    use McpTokenValidationRules;

    /**
     * Show the tokens that can reach the HTTP MCP server.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('mcp-tokens/Index', [
            'tokens' => $request->user()->tokens()
                ->latest()
                ->get()
                ->map(fn (PersonalAccessToken $token): array => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities ?? [],
                    'last_used_at' => $token->last_used_at?->diffForHumans(),
                    'expires_at' => $token->expires_at?->toDateString(),
                    'created_at' => $token->created_at?->toDateString(),
                ])
                ->all(),
            'abilityOptions' => $this->mcpAbilities(),
            'endpoint' => route('mcp.taskflow'),
            // Everything the connection snippets need, resolved server side so
            // the user can paste them without editing paths by hand.
            'phpBinary' => $this->phpBinary(),
            'artisanPath' => base_path('artisan'),
            'userEmail' => $request->user()->email,
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
     * Issue a new token for the MCP server.
     */
    public function store(TokenStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $expiresInDays = $validated['expires_in_days'] ?? null;

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'],
            $expiresInDays === null ? null : now()->addDays((int) $expiresInDays),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Token issued.')]);

        return to_route('mcp-tokens.index')->with('mcpToken', $token->plainTextToken);
    }

    /**
     * Revoke one of the user's tokens.
     */
    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        abort_unless(
            $token->tokenable_id === $request->user()->id && $token->tokenable_type === $request->user()->getMorphClass(),
            403,
        );

        $token->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Token revoked.')]);

        return to_route('mcp-tokens.index');
    }
}
