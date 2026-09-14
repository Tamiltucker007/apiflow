<?php

namespace App\Providers;

use App\Models\Plan;
use App\Observers\PlanObserver;
use App\Services\MerchantContext;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
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

        // There's no parameter-free "dashboard"/"home" route (dashboards are
        // per-merchant), so Laravel's default post-login redirect would fall
        // through to the "/" route -> which itself redirects to /login,
        // looping forever for an already-authenticated visitor. Send them
        // straight to their own merchant's dashboard instead.
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            $user = $request->user();

            return $user ? route('merchants.dashboard', $user->merchant_id) : route('login');
        });

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
