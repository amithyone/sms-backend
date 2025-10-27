<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\V1SyncService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register V1 Sync Service as singleton (DISABLED - V1 sync no longer used)
        $this->app->singleton(V1SyncService::class, function ($app) {
            return new V1SyncService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
