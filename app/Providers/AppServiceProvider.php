<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Rate Limiter for Admin: Strict (60 per minute)
        \Illuminate\Support\Facades\RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate Limiter for POS: High Performance (500 per minute)
        // Cashiers scan items fast, so we need a much higher limit.
        \Illuminate\Support\Facades\RateLimiter::for('pos', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
        });
    }
}
