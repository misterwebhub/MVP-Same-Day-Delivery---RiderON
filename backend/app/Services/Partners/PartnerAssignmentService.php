<?php

namespace App\Services\Partners;

use App\Models\DeliveryPartner;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Collection;

/**
 * Broadcasts a newly-booked order to every eligible delivery partner instead
 * of auto-assigning one. Per explicit product direction: no partner is
 * silently pre-selected — the order stays unassigned (`partner_id` null,
 * status RIDER_ASSIGNMENT_PENDING) and visible in every eligible partner's
 * "Unassigned" pool (PartnerAssignmentController::unassigned()) until
 * whoever accepts first claims it via POST /partner/assignments/{id}/accept
 * (an atomic `whereNull('partner_id')` update — first tap wins, everyone
 * else gets a 409).
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

    /**
     * @return Collection<int, DeliveryPartner> partners notified
     */
    public function attemptAssignment(Order $order): Collection
    {
        $order->loadMissing('route.originStation', 'route.destinationStation');
        $originCityId = $order->route->originStation->city_id;
        $destinationCityId = $order->route->destinationStation->city_id;

        $partners = DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->whereIn('current_home_city_id', array_unique([$originCityId, $destinationCityId]))
            ->orderBy('completed_deliveries_count')
            ->orderBy('id')
            ->get();

        if ($partners->isEmpty()) {
            return $partners;
        }

        $this->activityLogger->log(
            $order,
            OrderActivityLog::EVENT_PARTNERS_NOTIFIED,
            OrderActivityLog::ACTOR_SYSTEM,
            null,
            metadata: ['partner_ids' => $partners->pluck('id')->all()],
        );

        foreach ($partners as $partner) {
            $this->notifications->notifyUser(
                $partner->user_id,
                'new_order_available',
                'New delivery available',
                "Order {$order->booking_reference} is available — open Unassigned Rides to accept it.",
                [
                    'order_id' => $order->id,
                    // Routing hint for the partner app's notification-tap
                    // handler: land on the Rides tab's Unassigned section,
                    // not just the app's default screen.
                    'screen' => 'Rides',
                    'section' => 'unassigned',
                ],
                Notification::CHANNEL_PUSH,
            );
        }

        return $partners;
    }
}
