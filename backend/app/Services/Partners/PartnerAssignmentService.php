<?php

namespace App\Services\Partners;

use App\Models\DeliveryPartner;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\NotificationService;

/**
 * Matches a candidate delivery partner to an order sitting in
 * RIDER_ASSIGNMENT_PENDING (docs/06). This only sets `orders.partner_id` —
 * the order stays in RIDER_ASSIGNMENT_PENDING until the partner explicitly
 * accepts via POST /partner/assignments/{id}/accept. If no candidate is
 * found, the order is left unassigned (partner_id null) rather than faking
 * a match, per docs/09's "rider unavailable" edge case: honest
 * "finding a delivery partner" state, visible to admin for manual assignment.
 *
 * A partner travels a scheduled train/bus route and can carry multiple
 * parcels on the same trip, so having another live order on the same
 * booking_date does NOT make a partner ineligible — same-day multi-order
 * assignment to one partner is normal, not a double-booking conflict.
 *
 * Eligibility matches the partner's home city against EITHER end of the
 * order's route (origin or destination): a partner rides the corridor
 * round-trip — e.g. a Kanpur-based partner carries outbound parcels to
 * Lucknow, then also picks up parcels in Lucknow for the return leg back
 * to Kanpur — so they stay eligible for both directions of their home
 * corridor, not just orders originating from their home city.
 */
class PartnerAssignmentService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function attemptAssignment(Order $order): ?DeliveryPartner
    {
        $order->loadMissing('route.originStation', 'route.destinationStation');
        $originCityId = $order->route->originStation->city_id;
        $destinationCityId = $order->route->destinationStation->city_id;

        $partner = DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->whereIn('current_home_city_id', array_unique([$originCityId, $destinationCityId]))
            ->orderBy('completed_deliveries_count')
            ->orderBy('id')
            ->first();

        if ($partner === null) {
            return null;
        }

        $order->forceFill(['partner_id' => $partner->id])->save();

        $this->activityLogger->log(
            $order,
            OrderActivityLog::EVENT_PARTNER_MATCHED,
            OrderActivityLog::ACTOR_SYSTEM,
            null,
            metadata: ['partner_id' => $partner->id],
        );

        $this->notifications->notifyUser(
            $partner->user_id,
            'new_order_assigned',
            'New delivery assignment',
            "Order {$order->booking_reference} has been assigned to you. Open the app to accept it.",
            ['order_id' => $order->id],
            Notification::CHANNEL_PUSH,
        );

        return $partner;
    }
}
