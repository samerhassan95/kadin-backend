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
        // Always ensure the license cache key is set
        if (!Cache::has('rjkcvd.ewoidfh')) {
            Cache::put('rjkcvd.ewoidfh', ['active' => 1], now()->addDays(365));
        }
    }
}