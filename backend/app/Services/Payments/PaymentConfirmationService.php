<?php

namespace App\Services\Payments;

use App\Constants\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\InvalidPaymentSignatureException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OtpVerification;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Orders\OrderOtpExpiryCalculator;
use App\Services\Otp\OtpService;
use App\Services\Partners\PartnerAssignmentService;
use App\Services\PaymentGateway\PaymentGateway;
use App\StateMachines\OrderStateMachine;

/**
 * Single idempotent entry point for confirming a payment, shared by the
 * client-initiated POST /payments/{id}/verify callback and the
 * POST /payments/webhook/razorpay server-to-server webhook (docs/05).
 * Both paths converge here so whichever one runs first performs the
 * order transition + OTP generation, and whichever runs second (or a
 * retried duplicate of the same event) safely no-ops.
 */
class PaymentConfirmationService
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly OrderStateMachine $stateMachine,
        private readonly OtpService $otpService,
        private readonly OrderOtpExpiryCalculator $otpExpiryCalculator,
        private readonly PartnerAssignmentService $partnerAssignmentService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $signaturePayload  Shape expected by PaymentGateway::verifySignature().
     * @param  array<string, mixed>  $rawPayload  Full payload persisted verbatim for audit.
     */
    public function confirm(
        Payment $payment,
        string $eventType,
        array $signaturePayload,
        ?string $providerPaymentId,
        array $rawPayload,
        ?string $targetOrderStatus = null,
    ): PaymentTransaction {
        if ($providerPaymentId !== null) {
            $existing = PaymentTransaction::query()
                ->where('provider_payment_id', $providerPaymentId)
                ->where('event_type', $eventType)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $signatureVerified = $this->paymentGateway->verifySignature($signaturePayload);

        $transaction = PaymentTransaction::create([
            'payment_id' => $payment->id,
            'provider_payment_id' => $providerPaymentId,
            'provider_signature' => $signaturePayload['razorpay_signature'] ?? $signaturePayload['signature'] ?? null,
            'event_type' => $eventType,
            'raw_payload' => $rawPayload,
            'signature_verified' => $signatureVerified,
            'processed' => false,
            'created_at' => now(),
        ]);

        if (! $signatureVerified) {
            throw new InvalidPaymentSignatureException();
        }

        if ($targetOrderStatus !== null) {
            $this->applyOrderTransition($payment, $targetOrderStatus);
        }

        $transaction->forceFill(['processed' => true])->save();

        return $transaction;
    }

    private function applyOrderTransition(Payment $payment, string $targetOrderStatus): void
    {
        $order = $payment->order;

        try {
            $updated = $this->stateMachine->transition($order, $targetOrderStatus, OrderStatusHistory::ACTOR_SYSTEM);
        } catch (InvalidOrderTransitionException $e) {
            if (! $this->stateMachine->hasReached($order, $targetOrderStatus)) {
                throw $e;
            }

            // Already applied by a concurrent verify/webhook call — the side
            // effects below (OTP generation, auto-assignment) already ran
            // for that call, so this one must not repeat them.
            return;
        }

        if ($targetOrderStatus === OrderStatus::BOOKED) {
            $payment->forceFill(['status' => Payment::STATUS_SUCCESS])->save();
            $this->generateOtps($updated);

            $pending = $this->stateMachine->transitionIfNotAlready(
                $updated,
                OrderStatus::RIDER_ASSIGNMENT_PENDING,
                OrderStatusHistory::ACTOR_SYSTEM,
            );
            $this->partnerAssignmentService->attemptAssignment($pending);
        } elseif ($targetOrderStatus === OrderStatus::PAYMENT_FAILED) {
            $payment->forceFill(['status' => Payment::STATUS_FAILED])->save();
        }
    }

    /**
     * Both OTPs are generated the instant BOOKED is reached, per
     * docs/05-payment-otp-architecture.md. The delivery OTP's expiry is an
     * *estimate* (route duration + waiting time) since the real
     * waiting_deadline_at isn't known until ARRIVED_DESTINATION later in
     * the order's lifecycle.
     */
    private function generateOtps(Order $order): void
    {
        $this->otpService->generate(
            purpose: OtpVerification::PURPOSE_PICKUP,
            phone: $order->sender_phone,
            orderId: $order->id,
            userId: $order->customer_id,
            expiresAt: $this->otpExpiryCalculator->pickupExpiry($order),
        );

        $this->otpService->generate(
            purpose: OtpVerification::PURPOSE_DELIVERY,
            phone: $order->receiver_phone,
            orderId: $order->id,
            userId: $order->customer_id,
            expiresAt: $this->otpExpiryCalculator->deliveryExpiry($order),
        );
    }
}
