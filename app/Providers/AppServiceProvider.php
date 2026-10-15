<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configurePassport();
    }

    /**
     * Configure the OAuth server that fronts the HTTP MCP endpoint.
     */
    protected function configurePassport(): void
    {
        // Laravel MCP adds the `mcp:use` scope that the OAuth flow grants.
        // These two are for personal access tokens, which can be narrowed.
        Passport::tokensCan([
            'mcp:read' => 'Read projects and tasks',
            'mcp:write' => 'Create and change projects and tasks',
        ]);

        // Personal access tokens last a year unless the user picks a shorter
        // window on the MCP tokens page; OAuth access tokens are shorter lived
        // and refreshable.
        Passport::personalAccessTokensExpireIn(now()->addYear());
        Passport::tokensExpireIn(now()->addDays(30));
        Passport::refreshTokensExpireIn(now()->addDays(60));

        // The consent screen that ships with laravel/mcp: it posts to Passport's
        // approve/deny routes and closes the popup the client opened.
        Passport::authorizationView(
            fn (array $parameters) => response()->view('mcp.authorize', $parameters),
        );
    }

    /**
     * Configure the rate limiter used by the HTTP MCP server.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('mcp', fn (Request $request): Limit => $request->user()
            ? Limit::perMinute(120)->by('mcp:user:'.$request->user()->id)
            : Limit::perMinute(20)->by('mcp:ip:'.$request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
