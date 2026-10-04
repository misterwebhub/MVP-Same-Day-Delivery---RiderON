<?php

namespace App\Services\Refunds;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\PaymentGateway\PaymentGateway;
use RuntimeException;

/**
 * Admin-triggered refund path (the admin panel's "Approve Refund" action), separate
 * from OrderController's self-service cancellation refund. Always calls the
 * real PaymentGateway interface (mock or Razorpay per env) — never fakes a
 * completed refund without the gateway call.
 */
class RefundProcessor
{
    public function __construct(private readonly PaymentGateway $paymentGateway) {}

    public function createAndProcess(Order $order, int $amountPaise, string $reason, int $approverId): Refund
    {
        $payment = $this->latestSuccessfulPayment($order);
        $providerPaymentId = $this->verifiedProviderPaymentId($payment);

        $gatewayRefund = $this->paymentGateway->refund($providerPaymentId, $amountPaise);

        return Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'requested_by' => Refund::REQUESTED_BY_ADMIN,
            'reason' => $reason,
            'amount_paise' => $amountPaise,
            'status' => $this->mapGatewayStatus($gatewayRefund->status),
            'provider_refund_id' => $gatewayRefund->providerRefundId,
            'approved_by' => $approverId,
        ]);
    }

    public function approve(Refund $refund, int $approverId): Refund
    {
        $payment = $refund->payment ?? $this->latestSuccessfulPayment($refund->order);
        $providerPaymentId = $this->verifiedProviderPaymentId($payment);

        $gatewayRefund = $this->paymentGateway->refund($providerPaymentId, $refund->amount_paise);

        $refund->forceFill([
            'status' => $this->mapGatewayStatus($gatewayRefund->status),
            'provider_refund_id' => $gatewayRefund->providerRefundId,
            'approved_by' => $approverId,
        ])->save();

        return $refund;
    }

    private function latestSuccessfulPayment(Order $order): Payment
    {
        $payment = $order->payments()->where('status', Payment::STATUS_SUCCESS)->latest('id')->first();

        if ($payment === null) {
            throw new RuntimeException('Order has no successful payment to refund.');
        }

        return $payment;
    }

    private function verifiedProviderPaymentId(Payment $payment): string
    {
        $providerPaymentId = $payment->transactions()
            ->where('signature_verified', true)
            ->whereNotNull('provider_payment_id')
            ->latest('id')
            ->value('provider_payment_id');

        if ($providerPaymentId === null) {
            throw new RuntimeException('No verified payment transaction found for refund.');
        }

        return $providerPaymentId;
    }

    private function mapGatewayStatus(string $gatewayStatus): string
    {
        return match ($gatewayStatus) {
            'processed', 'captured' => Refund::STATUS_COMPLETED,
            default => Refund::STATUS_PROCESSING,
        };
    }
}
