<?php

namespace App\Providers;

use App\Services\PaymentGateway\MockPaymentGateway;
use App\Services\PaymentGateway\PaymentGateway;
use App\Services\PaymentGateway\RazorpayGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function () {
            return match (config('services.payment_driver')) {
                'razorpay' => new RazorpayGateway(
                    keyId: (string) config('services.razorpay.key_id'),
                    keySecret: (string) config('services.razorpay.key_secret'),
                    webhookSecret: (string) config('services.razorpay.webhook_secret'),
                ),
                default => $this->app->make(MockPaymentGateway::class),
            };
        });
    }
}
