<?php

namespace App\Providers;

use App\Services\Sms\HanuOtpProvider;
use App\Services\Sms\Msg91Provider;
use App\Services\Sms\MockSmsProvider;
use App\Services\Sms\SmsProvider;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsProvider::class, function () {
            return match (config('services.sms_driver')) {
                'msg91' => $this->app->make(Msg91Provider::class),
                'hanuotp' => new HanuOtpProvider(
                    apiKey: (string) config('services.hanuotp.api_key'),
                    baseUrl: (string) config('services.hanuotp.base_url'),
                    templateId: (string) config('services.hanuotp.template_id'),
                ),
                default => $this->app->make(MockSmsProvider::class),
            };
        });
    }
}
