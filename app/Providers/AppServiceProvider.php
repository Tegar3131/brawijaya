<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;



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
    RateLimiter::for('api-login', function (Request $request) {
        $email = Str::lower((string) $request->input('email', 'guest'));

        return Limit::perMinute(5)->by($email . '|' . $request->ip());
    });

    RateLimiter::for('api-search', function (Request $request) {
        $key = $request->user()?->id
            ? 'user:' . $request->user()->id
            : 'ip:' . $request->ip();

        return Limit::perMinute(60)->by($key);
    });

    RateLimiter::for('api-upload', function (Request $request) {
        $key = $request->user()?->id
            ? 'user:' . $request->user()->id
            : 'ip:' . $request->ip();

        return Limit::perMinute(10)->by($key);
    });
}
}
