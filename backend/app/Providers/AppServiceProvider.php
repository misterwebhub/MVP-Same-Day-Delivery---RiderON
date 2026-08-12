<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pure API app — never redirect unauthenticated requests to a "login" route.
        Authenticate::redirectUsing(fn () => null);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('otp-request', function (Request $request) {
            $phone = (string) $request->input('phone');

            return [
                Limit::perHour((int) config('auth_tokens.otp_request_max_per_phone_per_hour'))->by("otp-phone:{$phone}"),
                Limit::perHour((int) config('auth_tokens.otp_request_max_per_ip_per_hour'))->by('otp-ip:'.$request->ip()),
            ];
        });
    }
}
