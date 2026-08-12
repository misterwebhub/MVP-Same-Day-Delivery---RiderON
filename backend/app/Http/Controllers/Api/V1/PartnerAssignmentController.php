<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerAssignmentResource;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\StateMachines\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerAssignmentController extends Controller
{
    private const RELATIONS = [
        'route.originStation',
        'route.destinationStation',
        'routeSchedule',
        'parcel',
    ];

    public function __construct(private readonly OrderStateMachine $stateMachine)
    {
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

        $updated->load(self::RELATIONS);

        return $this->success(new PartnerAssignmentResource($updated), 'Marked arrived at destination.');
    }

    private function requirePartner(Request $request): DeliveryPartner
    {
        $partner = $request->user()->deliveryPartner;

        abort_unless($partner !== null, 403);

        return $partner;
    }
}
