<?php

namespace App\Services\Orders;

use App\Constants\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\OrderStatusHistory;
use App\Models\OtpVerification;
use App\Models\OtpVerificationLog;
use App\Services\Activity\ActivityLogger;
use App\Services\Otp\OtpService;
use App\StateMachines\OrderStateMachine;
use Illuminate\Http\Request;

/**
 * Order-bound pickup/delivery OTP verify + resend (docs/05), shared by both
 * purposes so their state-transition wiring can't drift apart. Verify
 * enforces the required order status BEFORE calling OtpService::verify()
 * (which mutates attempt_count/locks), so a wrong-stage call never burns an
 * attempt only to fail the subsequent transition.
 */
class OrderOtpVerificationService
{
    private const PICKUP = [
        'purpose' => OtpVerification::PURPOSE_PICKUP,
        'precondition' => OrderStatus::RIDER_ARRIVED_PICKUP,
        'target' => OrderStatus::PICKED_UP,
    ];

    private const DELIVERY = [
        'purpose' => OtpVerification::PURPOSE_DELIVERY,
        'precondition' => OrderStatus::WAITING_FOR_RECEIVER,
        'target' => OrderStatus::DELIVERED,
    ];

    public function __construct(
        private readonly OtpService $otpService,
        private readonly OrderStateMachine $stateMachine,
        private readonly OrderOtpExpiryCalculator $expiryCalculator,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function verifyPickup(Order $order, DeliveryPartner $partner, string $inputOtp, Request $request): Order
    {
        return $this->verify($order, $partner, $inputOtp, $request, self::PICKUP);
    }

    public function verifyDelivery(Order $order, DeliveryPartner $partner, string $inputOtp, Request $request): Order
    {
        return $this->verify($order, $partner, $inputOtp, $request, self::DELIVERY);
    }

    public function resendPickup(Order $order): OtpVerification
    {
        return $this->resend($order, self::PICKUP);
    }

    public function resendDelivery(Order $order): OtpVerification
    {
        return $this->resend($order, self::DELIVERY);
    }

    /**
     * Admin-only helper: resends (or, if none exists yet, generates) the
     * pickup OTP for an order and also returns the plaintext code so it can
     * be shown directly on the admin order page — for testing without
     * waiting on the SMS provider. See generateOtpWithPlainOtp() docblock
     * for why the plaintext is safe to surface here.
     *
     * @return array{0: OtpVerification, 1: string}
     */
    public function adminGeneratePickup(Order $order): array
    {
        return $this->adminGenerate($order, self::PICKUP, $order->sender_phone);
    }

    /**
     * @return array{0: OtpVerification, 1: string}
     */
    public function adminGenerateDelivery(Order $order): array
    {
        return $this->adminGenerate($order, self::DELIVERY, $order->receiver_phone);
    }

    /**
     * Partner-facing regenerate: the assigned partner can force a fresh
     * pickup OTP (e.g. sender says the SMS never arrived) without ever
     * seeing the code themselves — the plaintext returned here is only for
     * the caller to hand off to NotificationService (receiver message +
     * customer push + admin activity log), never serialized back to the
     * partner app. Reuses the same bypassLimits path as admin, since a
     * partner standing at the pickup point is exactly the situation the
     * resend cooldown/limit shouldn't block.
     *
     * @return array{0: OtpVerification, 1: string}
     */
    public function partnerRegeneratePickup(Order $order, DeliveryPartner $partner): array
    {
        abort_unless($order->partner_id === $partner->id, 403);

        return $this->adminGenerate($order, self::PICKUP, $order->sender_phone);
    }

    /**
     * @return array{0: OtpVerification, 1: string}
     */
    public function partnerRegenerateDelivery(Order $order, DeliveryPartner $partner): array
    {
        abort_unless($order->partner_id === $partner->id, 403);

        return $this->adminGenerate($order, self::DELIVERY, $order->receiver_phone);
    }

    /**
     * @param  array{purpose:string,precondition:string,target:string}  $spec
     * @return array{0: OtpVerification, 1: string}
     */
    private function adminGenerate(Order $order, array $spec, string $phone): array
    {
        $existing = OtpVerification::query()
            ->where('order_id', $order->id)
            ->where('purpose', $spec['purpose'])
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        $expiresAt = $spec['purpose'] === OtpVerification::PURPOSE_PICKUP
            ? $this->expiryCalculator->pickupExpiry($order)
            : $this->expiryCalculator->deliveryExpiry($order);

        if ($existing !== null) {
            // bypassLimits: true — an admin-triggered refresh must never be
            // blocked by the resend-count/cooldown guard that protects the
            // customer-facing resend button.
            return $this->otpService->resendWithPlainOtp($existing, $expiresAt, bypassLimits: true);
        }

        return $this->otpService->generateWithPlainOtp(
            purpose: $spec['purpose'],
            phone: $phone,
            orderId: $order->id,
            userId: $order->customer_id,
            expiresAt: $expiresAt,
        );
    }

    /**
     * @param  array{purpose:string,precondition:string,target:string}  $spec
     */
    private function verify(Order $order, DeliveryPartner $partner, string $inputOtp, Request $request, array $spec): Order
    {
        if ($order->status !== $spec['precondition']) {
            throw new InvalidOrderTransitionException($order->status, $spec['target']);
        }

        // Fraud-prevention: OTP alone only proves "the right person said the right
        // number" — it doesn't prove the rider actually had the parcel in hand. A
        // proof photo for this leg must exist before the OTP can complete the
        // transition, so custody is evidenced two ways, not one.
        $photoColumn = $spec['purpose'] === OtpVerification::PURPOSE_PICKUP
            ? 'pickup_proof_photo_path'
            : 'delivery_proof_photo_path';
        abort_if(
            $order->{$photoColumn} === null,
            422,
            'Upload a '.$spec['purpose'].' photo before verifying the OTP.',
        );

        $otp = OtpVerification::query()
            ->where('order_id', $order->id)
            ->where('purpose', $spec['purpose'])
            ->latest('id')
            ->firstOrFail();

        $this->otpService->verify(
            otpVerificationId: $otp->id,
            inputOtp: $inputOtp,
            attemptedByType: OtpVerificationLog::ATTEMPTED_BY_PARTNER,
            attemptedById: $partner->user_id,
            ipAddress: $request->ip(),
        );

        $updated = $this->stateMachine->transition(
            $order,
            $spec['target'],
            OrderStatusHistory::ACTOR_PARTNER,
            $partner->user_id,
            $spec['target'] === OrderStatus::DELIVERED ? ['delivered_at' => now()] : [],
        );

        $latitude = $request->input('latitude') !== null ? (float) $request->input('latitude') : null;
        $longitude = $request->input('longitude') !== null ? (float) $request->input('longitude') : null;

        $this->activityLogger->log(
            $updated,
            $spec['purpose'] === OtpVerification::PURPOSE_PICKUP
                ? OrderActivityLog::EVENT_PICKUP_OTP_VERIFIED
                : OrderActivityLog::EVENT_DELIVERY_OTP_VERIFIED,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            $request,
            $latitude,
            $longitude,
        );

        if ($spec['target'] === OrderStatus::PICKED_UP) {
            return $this->stateMachine->transitionIfNotAlready($updated, OrderStatus::IN_TRANSIT, OrderStatusHistory::ACTOR_SYSTEM);
        }

        if ($spec['target'] === OrderStatus::DELIVERED && (int) config('order.auto_complete_minutes') <= 0) {
            return $this->stateMachine->transitionIfNotAlready($updated, OrderStatus::COMPLETED, OrderStatusHistory::ACTOR_SYSTEM);
        }

        return $updated;
    }

    /**
     * @param  array{purpose:string,precondition:string,target:string}  $spec
     */
    private function resend(Order $order, array $spec): OtpVerification
    {
        $otp = OtpVerification::query()
            ->where('order_id', $order->id)
            ->where('purpose', $spec['purpose'])
            ->latest('id')
            ->firstOrFail();

        $expiresAt = $spec['purpose'] === OtpVerification::PURPOSE_PICKUP
            ? $this->expiryCalculator->pickupExpiry($order)
            : $this->expiryCalculator->deliveryExpiry($order);

        return $this->otpService->resend($otp, $expiresAt);
    }
}
