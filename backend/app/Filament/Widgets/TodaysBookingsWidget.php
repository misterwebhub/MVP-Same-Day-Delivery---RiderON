<?php

namespace App\Filament\Widgets;

use App\Constants\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaysBookingsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = today();

        $todaysOrders = Order::query()->whereDate('booking_date', $today);

        $bookingsCount = (clone $todaysOrders)->count();

        $revenuePaise = (clone $todaysOrders)
            ->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED])
            ->sum('total_amount_paise');

        $pendingAssignment = (clone $todaysOrders)
            ->where('status', OrderStatus::RIDER_ASSIGNMENT_PENDING)
            ->count();

        return [
            Stat::make("Today's bookings", (string) $bookingsCount),
            Stat::make("Today's revenue", '₹'.number_format($revenuePaise / 100, 2)),
            Stat::make('Awaiting partner assignment', (string) $pendingAssignment)
                ->color($pendingAssignment > 0 ? 'warning' : 'success'),
        ];
    }
}
