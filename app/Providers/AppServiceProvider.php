<?php

namespace App\Providers;

use App\Services\MerchantContext;
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
        //
    }
}
