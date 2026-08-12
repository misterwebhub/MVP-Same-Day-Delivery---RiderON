<?php

namespace App\Services\PaymentGateway;

final class PaymentGatewayRefund
{
    public function __construct(
        public readonly string $providerRefundId,
        public readonly int $amountPaise,
        public readonly string $status,
    ) {}
}
