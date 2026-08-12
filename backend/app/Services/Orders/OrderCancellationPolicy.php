<?php

namespace App\Services\Orders;

use App\Constants\OrderStatus;
use App\Models\AppSetting;
use App\Models\Order;
use Carbon\Carbon;

/**
 * Determines whether a customer can self-service cancel an order and, if
 * so, what refund percentage applies, per docs/04-state-machine.md's
 * cancellation eligibility table. Cutoff/fee values are admin-configurable
 * via app_settings (`cancellation.*` keys); config/cancellation.php only
 * supplies the fallback defaults used before any app_settings row exists.
 */
class OrderCancellationPolicy
{
    public function evaluate(Order $order): CancellationDecision
    {
        if (in_array($order->status, OrderStatus::ADMIN_ONLY_CANCELLABLE, true)) {
            return CancellationDecision::adminOnly();
        }

        if (! in_array($order->status, OrderStatus::SELF_SERVICE_CANCELLABLE, true)) {
            return CancellationDecision::notCancellable();
        }

        if (in_array($order->status, [OrderStatus::PAYMENT_PENDING, OrderStatus::PAYMENT_FAILED], true)) {
            return CancellationDecision::cancellable(refundPercentage: 0, requiresRefund: false);
        }

        if ($order->status === OrderStatus::BOOKED) {
            return CancellationDecision::cancellable(refundPercentage: 100, requiresRefund: true);
        }

        // RIDER_ASSIGNMENT_PENDING / RIDER_ASSIGNED / WAITING_FOR_PICKUP: cutoff-based fee.
        $cutoffMinutes = $this->settingInt(
            'cancellation.pre_pickup_cutoff_minutes',
            (int) config('cancellation.pre_pickup_cutoff_minutes'),
        );
        $feePercentageWithinCutoff = $this->settingInt(
            'cancellation.fee_percentage_within_cutoff',
            (int) config('cancellation.fee_percentage_within_cutoff'),
        );

        $departureAt = $this->departureAt($order);
        $withinCutoff = $departureAt !== null
            && Carbon::now()->greaterThan($departureAt->clone()->subMinutes($cutoffMinutes));

        $refundPercentage = $withinCutoff ? max(0, 100 - $feePercentageWithinCutoff) : 100;

        return CancellationDecision::cancellable(refundPercentage: $refundPercentage, requiresRefund: true);
    }

    private function departureAt(Order $order): ?Carbon
    {
        $schedule = $order->routeSchedule;

        if ($schedule === null || $order->booking_date === null) {
            return null;
        }

        return Carbon::parse($order->booking_date->toDateString().' '.$schedule->departure_time);
    }

    private function settingInt(string $key, int $default): int
    {
        $value = AppSetting::query()->where('key', $key)->value('value');

        return $value !== null ? (int) $value : $default;
    }
}
