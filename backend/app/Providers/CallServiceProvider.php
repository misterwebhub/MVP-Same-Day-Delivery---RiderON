<?php

namespace App\Providers;

use App\Services\Call\CallProvider;
use App\Services\Call\ExotelProvider;
use App\Services\Call\MockCallProvider;
use Illuminate\Support\ServiceProvider;

class CallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CallProvider::class, function () {
            return match (config('services.call_driver')) {
                'exotel' => $this->app->make(ExotelProvider::class),
                default => $this->app->make(MockCallProvider::class),
            };
        });
    }
}
