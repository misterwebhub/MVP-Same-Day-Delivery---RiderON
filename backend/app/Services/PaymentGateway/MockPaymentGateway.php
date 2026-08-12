<?php

namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dev/test double for PaymentGateway. Never calls out to a real network —
 * logs every call so the flow is inspectable in storage/logs. Pass
 * payload['force_failure'] = true to verifySignature() to deterministically
 * simulate a failed/tampered payment for testing.
 */
class MockPaymentGateway implements PaymentGateway
{
    public function createOrder(int $amountPaise, string $currency, string $receipt): PaymentGatewayOrder
    {
        $providerOrderId = 'mock_order_'.Str::random(14);

        Log::info('[MockPaymentGateway] createOrder', [
            'provider_order_id' => $providerOrderId,
            'amount_paise' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt,
        ]);

        return new PaymentGatewayOrder($providerOrderId, $amountPaise, $currency, $receipt, 'created');
    }

    public function verifySignature(array $payload): bool
    {
        $forceFailure = (bool) ($payload['force_failure'] ?? false);

        Log::info('[MockPaymentGateway] verifySignature', ['force_failure' => $forceFailure]);

        return ! $forceFailure;
    }

    public function fetchPayment(string $providerPaymentId): PaymentGatewayPayment
    {
        Log::info('[MockPaymentGateway] fetchPayment', ['provider_payment_id' => $providerPaymentId]);

        return new PaymentGatewayPayment(
            providerPaymentId: $providerPaymentId,
            providerOrderId: null,
            amountPaise: 0,
            currency: 'INR',
            status: 'captured',
            method: 'mock',
            raw: [],
        );
    }

    public function refund(string $providerPaymentId, int $amountPaise): PaymentGatewayRefund
    {
        $refundId = 'mock_refund_'.Str::random(14);

        Log::info('[MockPaymentGateway] refund', [
            'refund_id' => $refundId,
            'provider_payment_id' => $providerPaymentId,
            'amount_paise' => $amountPaise,
        ]);

        return new PaymentGatewayRefund($refundId, $amountPaise, 'processed');
    }
}
