<?php

namespace App\Services\PaymentGateway;

final class PaymentGatewayOrder
{
    public function __construct(
        public readonly string $providerOrderId,
        public readonly int $amountPaise,
        public readonly string $currency,
        public readonly string $receipt,
        public readonly string $status,
    ) {}
}
