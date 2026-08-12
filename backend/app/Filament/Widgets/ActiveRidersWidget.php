<?php

namespace App\Filament\Widgets;

use App\Constants\OrderStatus;
use App\Models\DeliveryPartner;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveRidersWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeCount = DeliveryPartner::query()->where('is_active', true)->count();

        $verifiedCount = DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->count();

        $onDeliveryCount = Order::query()
            ->whereNotNull('partner_id')
            ->whereNotIn('status', OrderStatus::TERMINAL)
            ->distinct('partner_id')
            ->count('partner_id');

        return [
            Stat::make('Active partners', (string) $activeCount),
            Stat::make('Verified partners', (string) $verifiedCount),
            Stat::make('Currently on a delivery', (string) $onDeliveryCount),
        ];
    }
}
