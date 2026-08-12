<?php

namespace App\StateMachines;

use App\Constants\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Transition table per docs/04-state-machine.md. Keyed by target status,
 * valued by the set of source statuses that may transition into it.
 */
class OrderStateMachine
{
    private const TRANSITIONS = [
        OrderStatus::PAYMENT_FAILED => [
            OrderStatus::PAYMENT_PENDING,
        ],
        OrderStatus::PAYMENT_PENDING => [
            OrderStatus::PAYMENT_FAILED,
        ],
        OrderStatus::BOOKED => [
            OrderStatus::PAYMENT_PENDING,
        ],
        OrderStatus::RIDER_ASSIGNMENT_PENDING => [
            OrderStatus::BOOKED,
        ],
        OrderStatus::RIDER_ASSIGNED => [
            OrderStatus::RIDER_ASSIGNMENT_PENDING,
        ],
        OrderStatus::WAITING_FOR_PICKUP => [
            OrderStatus::RIDER_ASSIGNED,
        ],
        OrderStatus::RIDER_ARRIVED_PICKUP => [
            OrderStatus::WAITING_FOR_PICKUP,
        ],
        OrderStatus::PICKED_UP => [
            OrderStatus::RIDER_ARRIVED_PICKUP,
        ],
        OrderStatus::IN_TRANSIT => [
            OrderStatus::PICKED_UP,
        ],
        OrderStatus::ARRIVED_DESTINATION => [
            OrderStatus::IN_TRANSIT,
        ],
        OrderStatus::WAITING_FOR_RECEIVER => [
            OrderStatus::ARRIVED_DESTINATION,
            OrderStatus::FAILED_DELIVERY,
        ],
        OrderStatus::DELIVERED => [
            OrderStatus::WAITING_FOR_RECEIVER,
        ],
        OrderStatus::FAILED_DELIVERY => [
            OrderStatus::WAITING_FOR_RECEIVER,
        ],
        OrderStatus::COMPLETED => [
            OrderStatus::DELIVERED,
            OrderStatus::DISPUTED,
        ],
        OrderStatus::CANCELLED => [
            OrderStatus::PAYMENT_PENDING,
            OrderStatus::PAYMENT_FAILED,
            OrderStatus::BOOKED,
            OrderStatus::RIDER_ASSIGNMENT_PENDING,
            OrderStatus::RIDER_ASSIGNED,
            OrderStatus::WAITING_FOR_PICKUP,
            OrderStatus::RIDER_ARRIVED_PICKUP,
            OrderStatus::FAILED_DELIVERY,
            OrderStatus::DISPUTED,
        ],
        OrderStatus::DISPUTED => [
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
            OrderStatus::ARRIVED_DESTINATION,
            OrderStatus::WAITING_FOR_RECEIVER,
        ],
        OrderStatus::REFUND_PENDING => [
            OrderStatus::FAILED_DELIVERY,
            OrderStatus::DISPUTED,
        ],
        OrderStatus::REFUNDED => [
            OrderStatus::CANCELLED,
            OrderStatus::REFUND_PENDING,
        ],
    ];

    /**
     * Move an order to a new status inside a locked transaction.
     *
     * @param  array<string, mixed>  $attributes  Additional order columns to persist atomically with the
     *                                             status change (e.g. cancelled_at, cancellation_reason,
     *                                             partner_id, waiting_deadline_at). Left to the caller so
     *                                             this class stays agnostic of any single transition's side effects.
     * @param  array<string, mixed>  $historyMetadata  Freeform context recorded on the order_status_history row.
     *
     * @throws InvalidOrderTransitionException if $fromStatus -> $toStatus is not an allowed transition.
     */
    public function transition(
        Order $order,
        string $toStatus,
        string $actorType,
        ?int $actorId = null,
        array $attributes = [],
        array $historyMetadata = [],
    ): Order {
        if (! OrderStatus::isValid($toStatus)) {
            throw new InvalidArgumentException("Unknown order status [{$toStatus}].");
        }

        return DB::transaction(function () use ($order, $toStatus, $actorType, $actorId, $attributes, $historyMetadata) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $fromStatus = $locked->status;

            if (! $this->canTransition($fromStatus, $toStatus)) {
                throw new InvalidOrderTransitionException($fromStatus, $toStatus);
            }

            $locked->fill($attributes);
            $locked->status = $toStatus;
            $locked->save();

            OrderStatusHistory::create([
                'order_id' => $locked->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'metadata' => $historyMetadata ?: null,
                'created_at' => now(),
            ]);

            event(new OrderStatusChanged($locked, $fromStatus, $toStatus, $actorType, $actorId));

            return $locked->refresh();
        });
    }

    /**
     * Same as transition(), but tolerates the target already having been
     * reached (by a concurrent request, or a prior auto-chained transition
     * earlier in this same request) as a safe no-op instead of throwing.
     * Used for "auto, once X" transitions (docs/04) that immediately follow
     * another transition, where a duplicate/losing webhook or retry could
     * otherwise see the order already progressed past $toStatus and
     * wrongly treat that as a genuine conflict.
     */
    public function transitionIfNotAlready(
        Order $order,
        string $toStatus,
        string $actorType,
        ?int $actorId = null,
        array $attributes = [],
        array $historyMetadata = [],
    ): Order {
        try {
            return $this->transition($order, $toStatus, $actorType, $actorId, $attributes, $historyMetadata);
        } catch (InvalidOrderTransitionException $e) {
            if (! $this->hasReached($order, $toStatus)) {
                throw $e;
            }

            return $order->fresh();
        }
    }

    /**
     * Whether $order has ever reached $status, regardless of what it has
     * since progressed to. Lets callers distinguish "already applied by a
     * concurrent call" (safe no-op) from a genuine conflict.
     */
    public function hasReached(Order $order, string $status): bool
    {
        return OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('to_status', $status)
            ->exists();
    }

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($fromStatus, self::TRANSITIONS[$toStatus] ?? [], true);
    }

    /**
     * @return array<int, string>
     */
    public function allowedSourceStatuses(string $toStatus): array
    {
        return self::TRANSITIONS[$toStatus] ?? [];
    }
}
