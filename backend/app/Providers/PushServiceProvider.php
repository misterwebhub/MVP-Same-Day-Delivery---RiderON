<?php

namespace App\Providers;

use App\Services\Push\FcmProvider;
use App\Services\Push\MockPushProvider;
use App\Services\Push\PushProvider;
use Illuminate\Support\ServiceProvider;

class PushServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PushProvider::class, function () {
            return match (config('services.push_driver')) {
                'fcm' => $this->app->make(FcmProvider::class),
                default => $this->app->make(MockPushProvider::class),
            };
        });
    }
}
