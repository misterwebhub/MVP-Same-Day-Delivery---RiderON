<?php

namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Http;

/**
 * Real Razorpay integration over their REST API (Basic Auth with
 * key id/secret — no SDK dependency needed). Only exercised when
 * PAYMENT_DRIVER=razorpay and RAZORPAY_* credentials are configured.
 */
class RazorpayGateway implements PaymentGateway
{
    private const BASE_URL = 'https://api.razorpay.com/v1';

    public function __construct(
        private readonly string $keyId,
        private readonly string $keySecret,
        private readonly string $webhookSecret,
    ) {}

    public function createOrder(int $amountPaise, string $currency, string $receipt): PaymentGatewayOrder
    {
        $data = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->asJson()
            ->post(self::BASE_URL.'/orders', [
                'amount' => $amountPaise,
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
            ])
            ->throw()
            ->json();

        return new PaymentGatewayOrder(
            providerOrderId: $data['id'],
            amountPaise: (int) $data['amount'],
            currency: $data['currency'],
            receipt: $data['receipt'] ?? $receipt,
            status: $data['status'],
        );
    }

    /**
     * Accepts either a checkout-callback payload
     * (razorpay_order_id, razorpay_payment_id, razorpay_signature)
     * or a webhook payload (body, signature), per docs/05.
     */
    public function verifySignature(array $payload): bool
    {
        if (isset($payload['razorpay_order_id'], $payload['razorpay_payment_id'], $payload['razorpay_signature'])) {
            $expected = hash_hmac(
                'sha256',
                $payload['razorpay_order_id'].'|'.$payload['razorpay_payment_id'],
                $this->keySecret
            );

            return hash_equals($expected, $payload['razorpay_signature']);
        }

        if (isset($payload['body'], $payload['signature'])) {
            $expected = hash_hmac('sha256', $payload['body'], $this->webhookSecret);

            return hash_equals($expected, $payload['signature']);
        }

        return false;
    }

    public function fetchPayment(string $providerPaymentId): PaymentGatewayPayment
    {
        $data = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->get(self::BASE_URL."/payments/{$providerPaymentId}")
            ->throw()
            ->json();

        return new PaymentGatewayPayment(
            providerPaymentId: $data['id'],
            providerOrderId: $data['order_id'] ?? null,
            amountPaise: (int) $data['amount'],
            currency: $data['currency'],
            status: $data['status'],
            method: $data['method'] ?? null,
            raw: $data,
        );
    }

    public function refund(string $providerPaymentId, int $amountPaise): PaymentGatewayRefund
    {
        $data = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->asJson()
            ->post(self::BASE_URL."/payments/{$providerPaymentId}/refund", [
                'amount' => $amountPaise,
            ])
            ->throw()
            ->json();

        return new PaymentGatewayRefund(
            providerRefundId: $data['id'],
            amountPaise: (int) $data['amount'],
            status: $data['status'],
        );
    }
}
