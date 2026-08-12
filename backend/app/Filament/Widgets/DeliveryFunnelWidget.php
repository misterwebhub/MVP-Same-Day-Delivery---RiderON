<?php

namespace App\Filament\Widgets;

use App\Constants\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Today's bookings broken down by where they sit in the delivery funnel.
 * Counts each stage directly off the indexed `status` column — no
 * denormalized aggregation table.
 */
class DeliveryFunnelWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = today();

        $countFor = fn (array $statuses): int => Order::query()
            ->whereDate('booking_date', $today)
            ->whereIn('status', $statuses)
            ->count();

        return [
            Stat::make('Booked', (string) $countFor([OrderStatus::BOOKED])),
            Stat::make('Rider assigned', (string) $countFor([
                OrderStatus::RIDER_ASSIGNMENT_PENDING,
                OrderStatus::RIDER_ASSIGNED,
                OrderStatus::WAITING_FOR_PICKUP,
                OrderStatus::RIDER_ARRIVED_PICKUP,
                OrderStatus::PICKUP_OTP_PENDING,
            ])),
            Stat::make('In transit', (string) $countFor([
                OrderStatus::PICKED_UP,
                OrderStatus::IN_TRANSIT,
                OrderStatus::ARRIVED_DESTINATION,
                OrderStatus::WAITING_FOR_RECEIVER,
                OrderStatus::DELIVERY_OTP_PENDING,
            ])),
            Stat::make('Delivered / completed', (string) $countFor([
                OrderStatus::DELIVERED,
                OrderStatus::COMPLETED,
            ]))->color('success'),
            Stat::make('Exceptions', (string) $countFor([
                OrderStatus::FAILED_DELIVERY,
                OrderStatus::DISPUTED,
                OrderStatus::REFUND_PENDING,
            ]))->color('danger'),
        ];
    }
}
