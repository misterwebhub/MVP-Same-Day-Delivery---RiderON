<?php

namespace App\Services\Orders;

use App\Models\Order;
use Carbon\Carbon;

/**
 * Computes the milestone-based expiry for order-bound OTPs (docs/05).
 * Shared by initial generation (PaymentConfirmationService) and resend
 * (OrderOtpVerificationService) so a resent OTP never drifts from the
 * original journey-anchored deadline.
 */
class OrderOtpExpiryCalculator
{
    public function pickupExpiry(Order $order): Carbon
    {
        return $this->departureAt($order)->clone()
            ->addMinutes((int) config('otp.pickup_expiry_buffer_minutes'));
    }

    /**
     * Estimate only — the real waiting_deadline_at isn't known until
     * ARRIVED_DESTINATION later in the order's lifecycle.
     */
    public function deliveryExpiry(Order $order): Carbon
    {
        $order->loadMissing('route');

        $estimatedArrivalAt = $this->departureAt($order)->clone()
            ->addMinutes((int) $order->route->estimated_duration_minutes)
            ->addMinutes((int) $order->route->waiting_time_minutes);

        return $estimatedArrivalAt->clone()
            ->addMinutes((int) config('otp.delivery_expiry_buffer_minutes'));
    }

    private function departureAt(Order $order): Carbon
    {
        $order->loadMissing('routeSchedule');

        return Carbon::parse($order->booking_date->toDateString().' '.$order->routeSchedule->departure_time);
    }
}
