<?php

namespace App\Services\Partners;

use App\Constants\OrderStatus;
use App\Models\DeliveryPartner;
use App\Models\Order;

/**
 * Matches a candidate delivery partner to an order sitting in
 * RIDER_ASSIGNMENT_PENDING (docs/06). This only sets `orders.partner_id` —
 * the order stays in RIDER_ASSIGNMENT_PENDING until the partner explicitly
 * accepts via POST /partner/assignments/{id}/accept. If no candidate is
 * found, the order is left unassigned (partner_id null) rather than faking
 * a match, per docs/09's "rider unavailable" edge case: honest
 * "finding a delivery partner" state, visible to admin for manual assignment.
 */
class PartnerAssignmentService
{
    public function attemptAssignment(Order $order): ?DeliveryPartner
    {
        $order->loadMissing('route.originStation');
        $originCityId = $order->route->originStation->city_id;

        $busyPartnerIds = Order::query()
            ->where('id', '!=', $order->id)
            ->where('booking_date', $order->booking_date)
            ->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED, OrderStatus::COMPLETED])
            ->whereNotNull('partner_id')
            ->pluck('partner_id');

        $partner = DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->where('current_home_city_id', $originCityId)
            ->whereNotIn('id', $busyPartnerIds)
            ->orderBy('completed_deliveries_count')
            ->orderBy('id')
            ->first();

        if ($partner === null) {
            return null;
        }

        $order->forceFill(['partner_id' => $partner->id])->save();

        return $partner;
    }
}
