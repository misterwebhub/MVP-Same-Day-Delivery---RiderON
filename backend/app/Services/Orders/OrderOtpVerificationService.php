<?php

namespace App\Services\Orders;

use App\Constants\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OtpVerification;
use App\Models\OtpVerificationLog;
use App\Services\Otp\OtpService;
use App\StateMachines\OrderStateMachine;

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
    ) {
    }

    public function verifyPickup(Order $order, DeliveryPartner $partner, string $inputOtp, ?string $ipAddress): Order
    {
        return $this->verify($order, $partner, $inputOtp, $ipAddress, self::PICKUP);
    }

    public function verifyDelivery(Order $order, DeliveryPartner $partner, string $inputOtp, ?string $ipAddress): Order
    {
        return $this->verify($order, $partner, $inputOtp, $ipAddress, self::DELIVERY);
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
     * @param  array{purpose:string,precondition:string,target:string}  $spec
     */
    private function verify(Order $order, DeliveryPartner $partner, string $inputOtp, ?string $ipAddress, array $spec): Order
    {
        if ($order->status !== $spec['precondition']) {
            throw new InvalidOrderTransitionException($order->status, $spec['target']);
        }

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
            ipAddress: $ipAddress,
        );

        $updated = $this->stateMachine->transition(
            $order,
            $spec['target'],
            OrderStatusHistory::ACTOR_PARTNER,
            $partner->user_id,
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
