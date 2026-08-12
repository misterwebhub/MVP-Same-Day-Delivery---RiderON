<?php

namespace App\Services\PaymentGateway;

/**
 * Provider-agnostic payment gateway contract. Controllers and services
 * depend only on this interface — see docs/05-payment-otp-architecture.md.
 * Bound to RazorpayGateway or MockPaymentGateway by PaymentServiceProvider
 * based on the PAYMENT_DRIVER env var.
 */
interface PaymentGateway
{
    public function createOrder(int $amountPaise, string $currency, string $receipt): PaymentGatewayOrder;

    public function verifySignature(array $payload): bool;

    public function fetchPayment(string $providerPaymentId): PaymentGatewayPayment;

    public function refund(string $providerPaymentId, int $amountPaise): PaymentGatewayRefund;
}
