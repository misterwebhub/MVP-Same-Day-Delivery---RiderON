<?php

namespace Tests\Unit;

use App\Constants\OrderStatus;
use App\Models\City;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\Route;
use App\Models\Station;
use App\Services\Partners\PartnerAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PartnerAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PartnerAssignmentService::class);
    }

    /** Builds an order whose route originates in $originCity. */
    private function makeOrder(City $originCity, string $bookingDate = '2026-01-01', array $overrides = []): Order
    {
        $originStation = Station::factory()->for($originCity, 'city')->create();
        $route = Route::factory()->for($originStation, 'originStation')->create();

        return Order::factory()
            ->for($route, 'route')
            ->create(array_merge([
                'booking_date' => $bookingDate,
                'status' => OrderStatus::RIDER_ASSIGNMENT_PENDING,
            ], $overrides));
    }

    /** Builds an order whose route runs $originCity -> $destinationCity, for corridor round-trip tests. */
    private function makeOrderOnCorridor(City $originCity, City $destinationCity, string $bookingDate = '2026-01-01', array $overrides = []): Order
    {
        $originStation = Station::factory()->for($originCity, 'city')->create();
        $destinationStation = Station::factory()->for($destinationCity, 'city')->create();
        $route = Route::factory()
            ->for($originStation, 'originStation')
            ->for($destinationStation, 'destinationStation')
            ->create();

        return Order::factory()
            ->for($route, 'route')
            ->create(array_merge([
                'booking_date' => $bookingDate,
                'status' => OrderStatus::RIDER_ASSIGNMENT_PENDING,
            ], $overrides));
    }

    /**
     * Per explicit product direction: nobody is auto-assigned any more.
     * attemptAssignment() only broadcasts to eligible partners and leaves
     * `partner_id` null so the order stays visible in everyone's Unassigned
     * pool until a partner explicitly accepts it.
     */
    public function test_notifies_eligible_partners_but_does_not_assign_anyone(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        $busier = DeliveryPartner::factory()->create([
            'current_home_city_id' => $city->id,
            'completed_deliveries_count' => 10,
        ]);
        $leastBusy = DeliveryPartner::factory()->create([
            'current_home_city_id' => $city->id,
            'completed_deliveries_count' => 2,
        ]);

        $notified = $this->service->attemptAssignment($order);

        $this->assertCount(2, $notified);
        $this->assertEqualsCanonicalizing(
            [$busier->id, $leastBusy->id],
            $notified->pluck('id')->all(),
        );
        $this->assertNull($order->fresh()->partner_id);
        $this->assertSame(OrderStatus::RIDER_ASSIGNMENT_PENDING, $order->fresh()->status);
    }

    public function test_excludes_inactive_partners(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        DeliveryPartner::factory()->inactive()->create(['current_home_city_id' => $city->id]);
        $eligible = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);

        $notified = $this->service->attemptAssignment($order);

        $this->assertSame([$eligible->id], $notified->pluck('id')->all());
        $this->assertNull($order->fresh()->partner_id);
    }

    public function test_excludes_unverified_partners(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        DeliveryPartner::factory()->unverified()->create(['current_home_city_id' => $city->id]);
        $eligible = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);

        $notified = $this->service->attemptAssignment($order);

        $this->assertSame([$eligible->id], $notified->pluck('id')->all());
        $this->assertNull($order->fresh()->partner_id);
    }

    public function test_excludes_partners_based_in_a_different_city(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();
        $order = $this->makeOrder($city);

        DeliveryPartner::factory()->create(['current_home_city_id' => $otherCity->id]);

        $notified = $this->service->attemptAssignment($order);

        $this->assertCount(0, $notified);
        $this->assertNull($order->fresh()->partner_id);
    }

    /**
     * A partner rides the corridor round-trip: outbound from their home
     * city, then back again picking up parcels at the far end. So a
     * Kanpur-based partner must be eligible for a Lucknow -> Kanpur return
     * order too, not just Kanpur -> Lucknow orders.
     */
    public function test_a_home_city_partner_is_eligible_for_the_return_leg_order(): void
    {
        $home = City::factory()->create();
        $farEnd = City::factory()->create();

        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $home->id]);

        // Return-leg order: originates at the far end, destined for the partner's home city.
        $returnOrder = $this->makeOrderOnCorridor($farEnd, $home);

        $notified = $this->service->attemptAssignment($returnOrder);

        $this->assertSame([$partner->id], $notified->pluck('id')->all());
        $this->assertNull($returnOrder->fresh()->partner_id);
    }

    public function test_a_partner_with_no_connection_to_either_end_of_the_route_is_not_eligible(): void
    {
        $origin = City::factory()->create();
        $destination = City::factory()->create();
        $unrelatedCity = City::factory()->create();

        DeliveryPartner::factory()->create(['current_home_city_id' => $unrelatedCity->id]);

        $order = $this->makeOrderOnCorridor($origin, $destination);

        $notified = $this->service->attemptAssignment($order);

        $this->assertCount(0, $notified);
    }

    /**
     * A partner travels a scheduled train/bus route and can carry multiple
     * parcels on the same trip, so already being assigned to another
     * non-terminal order the same day must NOT disqualify them from being
     * notified about a second (or third...) same-day order.
     */
    public function test_notifies_a_partner_already_on_another_non_terminal_order_the_same_day(): void
    {
        $city = City::factory()->create();
        $bookingDate = '2026-02-10';

        $busyPartner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $this->makeOrder($city, $bookingDate, [
            'status' => OrderStatus::RIDER_ASSIGNED,
            'partner_id' => $busyPartner->id,
        ]);

        $order = $this->makeOrder($city, $bookingDate);

        $notified = $this->service->attemptAssignment($order);

        $this->assertSame([$busyPartner->id], $notified->pluck('id')->all());
    }

    public function test_does_not_exclude_a_partner_whose_same_day_order_is_cancelled(): void
    {
        $city = City::factory()->create();
        $bookingDate = '2026-02-10';

        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $this->makeOrder($city, $bookingDate, [
            'status' => OrderStatus::CANCELLED,
            'partner_id' => $partner->id,
        ]);

        $order = $this->makeOrder($city, $bookingDate);

        $notified = $this->service->attemptAssignment($order);

        $this->assertSame([$partner->id], $notified->pluck('id')->all());
    }

    public function test_returns_an_empty_collection_and_leaves_the_order_unassigned_when_no_eligible_partner_exists(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        $notified = $this->service->attemptAssignment($order);

        $this->assertCount(0, $notified);
        $this->assertNull($order->fresh()->partner_id);
    }
}
