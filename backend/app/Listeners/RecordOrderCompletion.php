<?php

namespace App\Listeners;

use App\Constants\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\DB;

/**
 * OrderStatusChanged was already dispatched on every transition but had no
 * listeners anywhere in the app — completed_at (relied on by
 * PartnerEarningsController) and DeliveryPartner::completed_deliveries_count
 * (used for load-balanced assignment and shown in the partner app) were
 * therefore never actually populated when an order reached COMPLETED.
 * This closes that gap with a real, minimal side effect rather than leaving
 * the earnings/assignment features reading fields nothing ever writes.
 */
class RecordOrderCompletion
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->toStatus !== OrderStatus::COMPLETED) {
            return;
        }

        $order = $event->order;

        DB::transaction(function () use ($order) {
            if ($order->completed_at === null) {
                $order->forceFill(['completed_at' => now()])->save();
            }

            if ($order->partner_id !== null) {
                DeliveryPartner::query()
                    ->whereKey($order->partner_id)
                    ->increment('completed_deliveries_count');
            }
        });
    }
}
