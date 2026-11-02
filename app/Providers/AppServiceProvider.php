<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        // Behind a TLS-terminating proxy the request reads as http, and every asset and Livewire URL would follow it.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // A report is a few hundred KB and nobody profiles a server more than a handful of times an hour.
        RateLimiter::for('timings', fn (Request $request) => Limit::perHour(30)->by($request->ip()));
    }
}
