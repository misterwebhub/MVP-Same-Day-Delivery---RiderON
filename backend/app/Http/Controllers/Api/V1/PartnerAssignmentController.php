<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerAssignmentResource;
use App\Models\DeliveryPartner;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\OrderStatusHistory;
use App\Models\OtpVerification;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\NotificationService;
use App\Services\Orders\OrderOtpVerificationService;
use App\StateMachines\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerAssignmentController extends Controller
{
    private const RELATIONS = [
        'route.originStation',
        'route.destinationStation',
        'routeSchedule',
        'parcel.images',
    ];

    public function __construct(
        private readonly OrderStateMachine $stateMachine,
        private readonly OrderOtpVerificationService $otpVerification,
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    /**
     * @return array{0: ?float, 1: ?float} [latitude, longitude] — both null if
     *   the client didn't send them (denied permission, older app build, etc).
     *   GPS is always best-effort and must never block the underlying action.
     */
    private function requestCoords(Request $request): array
    {
        return [
            $request->input('latitude') !== null ? (float) $request->input('latitude') : null,
            $request->input('longitude') !== null ? (float) $request->input('longitude') : null,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $partner = $this->requirePartner($request);

        $orders = Order::query()
            ->where('partner_id', $partner->id)
            ->when($request->query('date'), fn ($query, $date) => $query->where('booking_date', $date))
            ->with(self::RELATIONS)
            ->orderBy('id')
            ->get();

        return $this->success(PartnerAssignmentResource::collection($orders));
    }

    /**
     * "Unassigned Rides" pool — orders no partner has been matched to yet
     * (PartnerAssignmentService found no eligible candidate at booking time,
     * or hasn't run yet), filtered to this partner's eligibility using the
     * same home-city-vs-route-endpoints rule as
     * PartnerAssignmentService::attemptAssignment() /
     * OrderRepository::eligiblePartners() so a rider only ever sees rides
     * they could actually service. Accepting one here claims it via
     * accept()'s atomic whereNull('partner_id') update.
     */
    public function unassigned(Request $request): JsonResponse
    {
        $partner = $this->requirePartner($request);
        $homeCityId = $partner->current_home_city_id;

        $orders = Order::query()
            ->whereNull('partner_id')
            ->where('status', OrderStatus::RIDER_ASSIGNMENT_PENDING)
            ->whereHas('route', function ($query) use ($homeCityId) {
                $query->where(function ($q) use ($homeCityId) {
                    $q->whereHas('originStation', fn ($oq) => $oq->where('city_id', $homeCityId))
                        ->orWhereHas('destinationStation', fn ($dq) => $dq->where('city_id', $homeCityId));
                });
            })
            ->with(self::RELATIONS)
            ->orderBy('id')
            ->get();

        return $this->success(PartnerAssignmentResource::collection($orders));
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);

        $order->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($order));
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $partner = $this->requirePartner($request);

        if ($order->partner_id === null) {
            $this->claimUnassignedOrder($order, $partner);
        }

        abort_unless($order->partner_id === $partner->id, 403);

        $updated = $this->stateMachine->transition(
            $order,
            OrderStatus::RIDER_ASSIGNED,
            OrderStatusHistory::ACTOR_PARTNER,
            $partner->user_id,
        );

        $updated = $this->stateMachine->transitionIfNotAlready(
            $updated,
            OrderStatus::WAITING_FOR_PICKUP,
            OrderStatusHistory::ACTOR_SYSTEM,
        );

        $this->activityLogger->log(
            $updated,
            OrderActivityLog::EVENT_PARTNER_ACCEPTED,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            $request,
        );

        // Customer-facing "who's carrying my parcel" push — the customer
        // app's OrderResource now also exposes the partner's name/phone/
        // vehicle + route once partner_id is set, so this just alerts them
        // that info is now available.
        $partner->loadMissing('user');
        $this->notifications->notifyUser(
            $updated->customer_id,
            'partner_assigned',
            'Rider assigned',
            "{$partner->user->name} accepted order {$updated->booking_reference} and is on the way to pickup.",
            ['order_id' => $updated->id, 'screen' => 'OrderDetails'],
            Notification::CHANNEL_PUSH,
        );

        $updated->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($updated), 'Assignment accepted.');
    }

    public function arrivedPickup(Request $request, Order $order): JsonResponse
    {
        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);

        $updated = $this->stateMachine->transition(
            $order,
            OrderStatus::RIDER_ARRIVED_PICKUP,
            OrderStatusHistory::ACTOR_PARTNER,
            $partner->user_id,
        );

        [$latitude, $longitude] = $this->requestCoords($request);
        $updated->loadMissing('route.originStation');
        $this->activityLogger->log(
            $updated,
            OrderActivityLog::EVENT_RIDER_ARRIVED_PICKUP,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            $request,
            $latitude,
            $longitude,
            $updated->route?->originStation,
        );

        $updated->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($updated), 'Marked arrived at pickup.');
    }

    /**
     * Manual override — normally auto-fires after pickup OTP verify
     * succeeds (docs/03/04), so this tolerates the order already being in
     * IN_TRANSIT as a safe no-op rather than erroring.
     */
    public function startTransit(Request $request, Order $order): JsonResponse
    {
        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);

        $updated = $this->stateMachine->transitionIfNotAlready(
            $order,
            OrderStatus::IN_TRANSIT,
            OrderStatusHistory::ACTOR_PARTNER,
            $partner->user_id,
        );

        $updated->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($updated), 'Transit started.');
    }

    public function arrivedDestination(Request $request, Order $order): JsonResponse
    {
        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);

        $order->loadMissing('route');
        $waitingTimeMinutes = (int) $order->route->waiting_time_minutes;

        $updated = $this->stateMachine->transition(
            $order,
            OrderStatus::ARRIVED_DESTINATION,
            OrderStatusHistory::ACTOR_PARTNER,
            $partner->user_id,
            ['arrived_destination_at' => now()],
        );

        $updated = $this->stateMachine->transitionIfNotAlready(
            $updated,
            OrderStatus::WAITING_FOR_RECEIVER,
            OrderStatusHistory::ACTOR_SYSTEM,
            null,
            ['waiting_deadline_at' => now()->clone()->addMinutes($waitingTimeMinutes)],
        );

        [$latitude, $longitude] = $this->requestCoords($request);
        $updated->loadMissing('route.destinationStation');
        $this->activityLogger->log(
            $updated,
            OrderActivityLog::EVENT_RIDER_ARRIVED_DESTINATION,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            $request,
            $latitude,
            $longitude,
            $updated->route?->destinationStation,
        );

        $updated->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($updated), 'Marked arrived at destination.');
    }

    /**
     * Partner-initiated OTP regenerate. Never returns the plaintext code to
     * the partner (PartnerAssignmentResource already omits OTP values by
     * design — this response follows the same rule) — the rider only learns
     * whether it worked and when the new code expires. The actual code is
     * handed off to NotificationService so it reaches the sender/receiver
     * (whichever party owns this purpose's OTP) and the customer app, with
     * an admin activity-log entry either way.
     */
    public function regenerateOtp(Request $request, Order $order, string $purpose): JsonResponse
    {
        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);
        abort_unless(in_array($purpose, [OtpVerification::PURPOSE_PICKUP, OtpVerification::PURPOSE_DELIVERY], true), 404);

        [$otp, $plainOtp] = $purpose === OtpVerification::PURPOSE_PICKUP
            ? $this->otpVerification->partnerRegeneratePickup($order, $partner)
            : $this->otpVerification->partnerRegenerateDelivery($order, $partner);

        $this->notifyOtpRegenerated($order, $purpose, $plainOtp, $partner);

        $this->activityLogger->log(
            $order,
            $purpose === OtpVerification::PURPOSE_PICKUP
                ? OrderActivityLog::EVENT_PICKUP_OTP_REGENERATED
                : OrderActivityLog::EVENT_DELIVERY_OTP_REGENERATED,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            $request,
        );

        return $this->success([
            'purpose' => $purpose,
            'expires_at' => $otp->expires_at,
        ], ucfirst($purpose).' OTP regenerated. The code was sent to the '.($purpose === OtpVerification::PURPOSE_PICKUP ? 'sender' : 'receiver').'.');
    }

    private function notifyOtpRegenerated(Order $order, string $purpose, string $plainOtp, DeliveryPartner $partner): void
    {
        $isPickup = $purpose === OtpVerification::PURPOSE_PICKUP;
        $partyPhone = $isPickup ? $order->sender_phone : $order->receiver_phone;
        $partyLabel = $isPickup ? 'sender' : 'receiver';

        $this->notifications->notifyReceiverByPhone(
            $partyPhone,
            "Your RiderON {$purpose} OTP is {$plainOtp}. Share it only with the rider in person. — order {$order->booking_reference}",
        );

        $this->notifications->notifyUser(
            $order->customer_id,
            'otp_regenerated',
            ucfirst($purpose).' OTP refreshed',
            "The rider requested a new {$purpose} OTP for order {$order->booking_reference}. The {$partyLabel}'s code has changed — open the order to see the latest one.",
            ['order_id' => $order->id, 'purpose' => $purpose],
            Notification::CHANNEL_PUSH,
        );

        $this->notifications->notifyAdmins(
            'otp_regenerated',
            ucfirst($purpose).' OTP regenerated by rider',
            "Partner {$partner->partner_code} regenerated the {$purpose} OTP for order {$order->booking_reference}.",
            ['order_id' => $order->id, 'purpose' => $purpose, 'partner_id' => $partner->id],
        );
    }

    /**
     * Fraud-prevention evidence: the rider photographs the parcel at pickup
     * and at handoff. Required (see OrderOtpVerificationService::verify)
     * before the corresponding OTP can be verified — a rider can't complete
     * pickup/delivery on OTP alone anymore, they also have to prove they
     * physically had the parcel in front of them.
     */
    public function uploadPickupPhoto(Request $request, Order $order): JsonResponse
    {
        return $this->uploadProofPhoto($request, $order, 'pickup_proof_photo_path');
    }

    public function uploadDeliveryPhoto(Request $request, Order $order): JsonResponse
    {
        return $this->uploadProofPhoto($request, $order, 'delivery_proof_photo_path');
    }

    private function uploadProofPhoto(Request $request, Order $order, string $column): JsonResponse
    {
        $partner = $this->requirePartner($request);
        abort_unless($order->partner_id === $partner->id, 403);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('photo')->store('order-proofs', 'public');
        $order->forceFill([$column => $path])->save();

        [$latitude, $longitude] = $this->requestCoords($request);
        $isPickup = $column === 'pickup_proof_photo_path';
        $order->loadMissing($isPickup ? 'route.originStation' : 'route.destinationStation');
        $this->activityLogger->log(
            $order,
            $isPickup ? OrderActivityLog::EVENT_PICKUP_PHOTO_UPLOADED : OrderActivityLog::EVENT_DELIVERY_PHOTO_UPLOADED,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            $request,
            $latitude,
            $longitude,
            $isPickup ? $order->route?->originStation : $order->route?->destinationStation,
        );

        $this->notifications->notifyUser(
            $order->customer_id,
            'proof_photo_uploaded',
            $column === 'pickup_proof_photo_path' ? 'Pickup photo added' : 'Delivery photo added',
            "The rider added a photo as proof for order {$order->booking_reference}.",
            ['order_id' => $order->id],
            Notification::CHANNEL_PUSH,
        );

        $order->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($order), 'Photo uploaded.');
    }

    /**
     * Claims a still-unassigned order for this partner as part of accept().
     * Re-verifies eligibility server-side (never trust the client's
     * "unassigned" listing alone) and uses a conditional
     * whereNull('partner_id') update so two partners racing to accept the
     * same pooled ride can't both win — whoever's UPDATE actually matches
     * a NULL row gets it, the loser gets a 409.
     */
    private function claimUnassignedOrder(Order $order, DeliveryPartner $partner): void
    {
        $order->loadMissing('route.originStation', 'route.destinationStation');
        $originCityId = $order->route->originStation->city_id;
        $destinationCityId = $order->route->destinationStation->city_id;

        abort_unless(
            in_array($partner->current_home_city_id, [$originCityId, $destinationCityId], true),
            403,
            'Not eligible for this ride.',
        );

        $claimed = Order::query()
            ->where('id', $order->id)
            ->whereNull('partner_id')
            ->update(['partner_id' => $partner->id]);

        abort_if($claimed === 0, 409, 'This ride was just claimed by another partner.');

        $this->activityLogger->log(
            $order,
            OrderActivityLog::EVENT_PARTNER_MATCHED,
            OrderActivityLog::ACTOR_PARTNER,
            $partner->user_id,
            metadata: ['partner_id' => $partner->id, 'claimed_by_partner' => true],
        );

        $order->refresh();
    }

    private function requirePartner(Request $request): DeliveryPartner
    {
        $partner = $request->user()->deliveryPartner;

        abort_unless($partner !== null, 403);

        return $partner;
    }
}
