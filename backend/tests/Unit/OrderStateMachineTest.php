<?php

namespace Tests\Unit;

use App\Constants\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\StateMachines\OrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private OrderStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machine = new OrderStateMachine();
    }

    public function test_allows_a_valid_transition_and_records_history(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $updated = $this->machine->transition(
            $order,
            OrderStatus::RIDER_ASSIGNMENT_PENDING,
            OrderStatusHistory::ACTOR_SYSTEM,
            null,
        );

        $this->assertSame(OrderStatus::RIDER_ASSIGNMENT_PENDING, $updated->status);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::BOOKED,
            'to_status' => OrderStatus::RIDER_ASSIGNMENT_PENDING,
            'changed_by_type' => OrderStatusHistory::ACTOR_SYSTEM,
        ]);
    }

    public function test_persists_additional_attributes_atomically_with_the_transition(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $updated = $this->machine->transition(
            $order,
            OrderStatus::CANCELLED,
            OrderStatusHistory::ACTOR_ADMIN,
            1,
            [
                'cancelled_at' => now(),
                'cancellation_reason' => 'Testing cancellation',
                'cancelled_by' => Order::CANCELLED_BY_ADMIN,
            ],
        );

        $this->assertSame(OrderStatus::CANCELLED, $updated->status);
        $this->assertSame('Testing cancellation', $updated->cancellation_reason);
        $this->assertNotNull($updated->cancelled_at);
    }

    public function test_rejects_an_invalid_transition_and_leaves_the_order_unchanged(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $this->expectException(InvalidOrderTransitionException::class);

        try {
            $this->machine->transition($order, OrderStatus::DELIVERED, OrderStatusHistory::ACTOR_SYSTEM);
        } finally {
            $this->assertSame(OrderStatus::BOOKED, $order->fresh()->status);
            $this->assertDatabaseMissing('order_status_history', ['order_id' => $order->id]);
        }
    }

    public function test_rejects_an_unknown_target_status(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $this->expectException(\InvalidArgumentException::class);

        $this->machine->transition($order, 'NOT_A_REAL_STATUS', OrderStatusHistory::ACTOR_SYSTEM);
    }

    public function test_dispatches_order_status_changed_event(): void
    {
        Event::fake();

        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $this->machine->transition($order, OrderStatus::RIDER_ASSIGNMENT_PENDING, OrderStatusHistory::ACTOR_SYSTEM);

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event) use ($order) {
            return $event->order->id === $order->id
                && $event->fromStatus === OrderStatus::BOOKED
                && $event->toStatus === OrderStatus::RIDER_ASSIGNMENT_PENDING;
        });
    }

    public function test_can_transition_reflects_the_transition_table(): void
    {
        $this->assertTrue($this->machine->canTransition(OrderStatus::BOOKED, OrderStatus::RIDER_ASSIGNMENT_PENDING));
        $this->assertFalse($this->machine->canTransition(OrderStatus::BOOKED, OrderStatus::DELIVERED));
    }

    public function test_transition_if_not_already_is_a_safe_no_op_once_the_target_was_already_reached(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $first = $this->machine->transition($order, OrderStatus::RIDER_ASSIGNMENT_PENDING, OrderStatusHistory::ACTOR_SYSTEM);
        $first->forceFill(['status' => OrderStatus::RIDER_ASSIGNED])->save();

        // A losing concurrent caller retries the RIDER_ASSIGNMENT_PENDING transition
        // after the order has already progressed past it — should not throw.
        $result = $this->machine->transitionIfNotAlready($order->fresh(), OrderStatus::RIDER_ASSIGNMENT_PENDING, OrderStatusHistory::ACTOR_SYSTEM);

        $this->assertSame(OrderStatus::RIDER_ASSIGNED, $result->status);
    }

    public function test_transition_if_not_already_still_throws_for_a_genuinely_invalid_transition(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::BOOKED]);

        $this->expectException(InvalidOrderTransitionException::class);

        $this->machine->transitionIfNotAlready($order, OrderStatus::DELIVERED, OrderStatusHistory::ACTOR_SYSTEM);
    }

    public function test_allowed_source_statuses_matches_the_transition_table(): void
    {
        $sources = $this->machine->allowedSourceStatuses(OrderStatus::DISPUTED);

        $this->assertSame([
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
            OrderStatus::ARRIVED_DESTINATION,
            OrderStatus::WAITING_FOR_RECEIVER,
        ], $sources);
    }
}
