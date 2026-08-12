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

        $this->service = new PartnerAssignmentService();
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

    public function test_assigns_the_eligible_partner_with_the_fewest_completed_deliveries(): void
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

        $assigned = $this->service->attemptAssignment($order);

        $this->assertNotNull($assigned);
        $this->assertSame($leastBusy->id, $assigned->id);
        $this->assertSame($leastBusy->id, $order->fresh()->partner_id);
        $this->assertNotSame($busier->id, $assigned->id);
    }

    public function test_breaks_ties_on_completed_deliveries_by_lowest_partner_id(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        $first = DeliveryPartner::factory()->create([
            'current_home_city_id' => $city->id,
            'completed_deliveries_count' => 5,
        ]);
        $second = DeliveryPartner::factory()->create([
            'current_home_city_id' => $city->id,
            'completed_deliveries_count' => 5,
        ]);

        $assigned = $this->service->attemptAssignment($order);

        $this->assertSame($first->id, $assigned->id);
        $this->assertLessThan($second->id, $first->id);
    }

    public function test_excludes_inactive_partners(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        DeliveryPartner::factory()->inactive()->create(['current_home_city_id' => $city->id]);
        $eligible = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);

        $assigned = $this->service->attemptAssignment($order);

        $this->assertSame($eligible->id, $assigned->id);
    }

    public function test_excludes_unverified_partners(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        DeliveryPartner::factory()->unverified()->create(['current_home_city_id' => $city->id]);
        $eligible = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);

        $assigned = $this->service->attemptAssignment($order);

        $this->assertSame($eligible->id, $assigned->id);
    }

    public function test_excludes_partners_based_in_a_different_city(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();
        $order = $this->makeOrder($city);

        DeliveryPartner::factory()->create(['current_home_city_id' => $otherCity->id]);

        $assigned = $this->service->attemptAssignment($order);

        $this->assertNull($assigned);
        $this->assertNull($order->fresh()->partner_id);
    }

    public function test_excludes_partners_already_assigned_to_another_non_terminal_order_the_same_day(): void
    {
        $city = City::factory()->create();
        $bookingDate = '2026-02-10';

        $busyPartner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $this->makeOrder($city, $bookingDate, [
            'status' => OrderStatus::RIDER_ASSIGNED,
            'partner_id' => $busyPartner->id,
        ]);

        $freePartner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $order = $this->makeOrder($city, $bookingDate);

        $assigned = $this->service->attemptAssignment($order);

        $this->assertSame($freePartner->id, $assigned->id);
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

        $assigned = $this->service->attemptAssignment($order);

        $this->assertSame($partner->id, $assigned->id);
    }

    public function test_returns_null_and_leaves_the_order_unassigned_when_no_eligible_partner_exists(): void
    {
        $city = City::factory()->create();
        $order = $this->makeOrder($city);

        $assigned = $this->service->attemptAssignment($order);

        $this->assertNull($assigned);
        $this->assertNull($order->fresh()->partner_id);
    }
}
