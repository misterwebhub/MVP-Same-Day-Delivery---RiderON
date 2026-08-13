<?php

namespace Tests\Feature;

use App\Constants\OrderStatus;
use App\Models\City;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\Route;
use App\Models\Station;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the new "Unassigned Rides" pool endpoint and accept()'s extended
 * ability to atomically claim a still-unassigned order, per the rider-app
 * navigation-simplification request — this is the backend half of "don't
 * solve the visibility issue only at the UI level".
 */
class PartnerUnassignedRidesTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(City $originCity, array $overrides = []): Order
    {
        $originStation = Station::factory()->for($originCity, 'city')->create();
        $route = Route::factory()->for($originStation, 'originStation')->create();

        return Order::factory()
            ->for($route, 'route')
            ->create(array_merge([
                'booking_date' => '2026-08-14',
                'status' => OrderStatus::RIDER_ASSIGNMENT_PENDING,
                'partner_id' => null,
            ], $overrides));
    }

    public function test_unassigned_endpoint_returns_only_eligible_unassigned_orders(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();

        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);

        $eligible = $this->makeOrder($city);
        $wrongCity = $this->makeOrder($otherCity);
        $alreadyAssigned = $this->makeOrder($city, ['partner_id' => $partner->id, 'status' => OrderStatus::RIDER_ASSIGNED]);
        $notYetBooked = $this->makeOrder($city, ['status' => OrderStatus::PAYMENT_PENDING]);

        $response = $this->actingAs($partner->user, 'sanctum')->getJson('/api/v1/partner/assignments/unassigned');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($eligible->id));
        $this->assertFalse($ids->contains($wrongCity->id));
        $this->assertFalse($ids->contains($alreadyAssigned->id));
        $this->assertFalse($ids->contains($notYetBooked->id));
    }

    public function test_accept_claims_a_still_unassigned_eligible_order(): void
    {
        $city = City::factory()->create();
        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $order = $this->makeOrder($city);

        $response = $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/assignments/{$order->id}/accept", [], ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()]);

        $response->assertOk();

        $fresh = $order->fresh();
        $this->assertSame($partner->id, $fresh->partner_id);
        $this->assertSame(OrderStatus::WAITING_FOR_PICKUP, $fresh->status);
    }

    public function test_accept_rejects_a_partner_ineligible_for_the_unassigned_order(): void
    {
        $city = City::factory()->create();
        $otherCity = City::factory()->create();
        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $otherCity->id]);
        $order = $this->makeOrder($city);

        $response = $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/assignments/{$order->id}/accept", [], ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()]);

        $response->assertStatus(403);
        $this->assertNull($order->fresh()->partner_id);
    }

    /**
     * By the time this request is handled, the order is no longer in the
     * unassigned pool (another partner already fully claimed it moments
     * earlier) — the route-model-bound $order already reflects that, so
     * this hits the ordinary "not yours" 403 rather than claimUnassignedOrder()'s
     * whereNull() race branch (which only fires in the narrower window where
     * two requests both still see partner_id as null — proven directly by
     * the whereNull()+affected-rows-zero check in claimUnassignedOrder()).
     */
    public function test_accept_rejects_an_order_another_partner_already_fully_claimed(): void
    {
        $city = City::factory()->create();
        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $order = $this->makeOrder($city);

        // Simulate another partner winning the race a moment earlier.
        $order->forceFill(['partner_id' => DeliveryPartner::factory()->create(['current_home_city_id' => $city->id])->id])->save();

        $response = $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/assignments/{$order->id}/accept", [], ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()]);

        $response->assertStatus(403);
        $this->assertNotSame($partner->id, $order->fresh()->partner_id);
    }

    public function test_existing_push_assigned_accept_flow_still_works_unchanged(): void
    {
        $city = City::factory()->create();
        $partner = DeliveryPartner::factory()->create(['current_home_city_id' => $city->id]);
        $order = $this->makeOrder($city, ['partner_id' => $partner->id]);

        $response = $this->actingAs($partner->user, 'sanctum')
            ->postJson("/api/v1/partner/assignments/{$order->id}/accept", [], ['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()]);

        $response->assertOk();
        $this->assertSame(OrderStatus::WAITING_FOR_PICKUP, $order->fresh()->status);
    }
}
