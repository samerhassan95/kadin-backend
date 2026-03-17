<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;

class LicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set license cache for local development
        if (app()->environment(['local', 'development'])) {
            if (!Cache::has('rjkcvd.ewoidfh')) {
                Cache::put('rjkcvd.ewoidfh', ['active' => 1], now()->addDays(365));
            }
        }
    }
}