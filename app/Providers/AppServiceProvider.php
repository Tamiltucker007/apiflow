<?php

namespace App\Providers;

use App\Models\Plan;
use App\Observers\PlanObserver;
use App\Services\MerchantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Must be a singleton: EnsureMerchantAccess sets it once per request,
        // and MerchantScope/BelongsToMerchant read it later in the same request.
        $this->app->singleton(MerchantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Plan::observe(PlanObserver::class);

        // Auth/guest redirects for both guards live in dedicated middleware
        // (see app/Http/Middleware/) instead of here — not Laravel's default
        // 'auth'/'guest' hooks, which only resolve to one global route('login').

        // 120 req/min per API credential (not per IP). Keyed off the raw
        // header rather than the apiCredential request attribute: Laravel's
        // internal middleware priority list runs ThrottleRequests before
        // custom aliases like auth.apikey regardless of route-declared
        // order, so the attribute isn't set yet when this closure runs.
        RateLimiter::for('api-credential', function (Request $request) {
            $apiKey = $request->bearerToken() ?? $request->header('X-API-Key');
            $key = $apiKey ? hash('sha256', $apiKey) : $request->ip();

            return Limit::perMinute(120)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $headers['Retry-After'] ?? null,
                ], 429, $headers);
            });
        });
    }
}
