<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A minimal side-effecting endpoint behind the real 'idempotent'
        // middleware alias, so the middleware is exercised exactly as
        // registered in bootstrap/app.php without coupling to order/payment
        // business logic that isn't what this test is verifying.
        Route::post('/__test/idempotent-counter', function () {
            $count = (int) cache('idempotent_test_counter', 0) + 1;
            cache(['idempotent_test_counter' => $count], 60);

            return response()->json(['count' => $count], 201);
        })->middleware(['api', 'idempotent']);
    }

    public function test_a_repeated_request_with_the_same_key_replays_the_original_response_without_rerunning_the_handler(): void
    {
        $first = $this->postJson('/__test/idempotent-counter', [], ['Idempotency-Key' => 'key-abc']);
        $first->assertStatus(201)->assertJson(['count' => 1]);

        $second = $this->postJson('/__test/idempotent-counter', [], ['Idempotency-Key' => 'key-abc']);

        $second->assertStatus(201)->assertJson(['count' => 1]);
        $this->assertSame(1, cache('idempotent_test_counter'));
    }

    public function test_a_different_key_runs_the_handler_again(): void
    {
        $this->postJson('/__test/idempotent-counter', [], ['Idempotency-Key' => 'key-one'])
            ->assertStatus(201)->assertJson(['count' => 1]);

        $this->postJson('/__test/idempotent-counter', [], ['Idempotency-Key' => 'key-two'])
            ->assertStatus(201)->assertJson(['count' => 2]);
    }

    public function test_a_missing_idempotency_key_is_rejected(): void
    {
        $response = $this->postJson('/__test/idempotent-counter', []);

        $response->assertStatus(400);
        $this->assertNull(cache('idempotent_test_counter'));
    }

    public function test_the_orders_store_endpoint_requires_an_idempotency_key(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/orders', []);

        $response->assertStatus(400);
    }
}
