<?php

namespace App\Services\PaymentGateway;

final class PaymentGatewayPayment
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $providerPaymentId,
        public readonly ?string $providerOrderId,
        public readonly int $amountPaise,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $method,
        public readonly array $raw = [],
    ) {}
}
