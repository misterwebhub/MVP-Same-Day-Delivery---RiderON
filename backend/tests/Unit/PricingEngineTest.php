<?php

namespace Tests\Unit;

use App\Exceptions\InvalidQuoteTokenException;
use App\Models\Coupon;
use App\Models\PricingRule;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private PricingEngine $engine;

    private Route $route;

    private RouteSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new PricingEngine();
        $this->route = Route::factory()->create();
        $this->schedule = RouteSchedule::factory()->for($this->route, 'route')->create();
    }

    private function baseInput(array $overrides = []): array
    {
        return array_merge([
            'route_id' => $this->route->id,
            'route_schedule_id' => $this->schedule->id,
            'weight_slab' => 'upto_1kg',
            'quantity' => 1,
            'declared_value_paise' => 50000,
            'coupon_code' => null,
        ], $overrides);
    }

    public function test_calculates_base_fare_and_matching_weight_slab_charge_multiplied_by_quantity(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_WEIGHT_SLAB,
            'min_weight_grams' => 0,
            'max_weight_grams' => 1000,
            'amount_paise' => 2000,
        ]);

        $quote = $this->engine->quote($this->baseInput(['quantity' => 2]));

        $this->assertSame(24000, $quote->totalAmountPaise);
        $this->assertSame([
            ['label' => 'Base Fare', 'amount_paise' => 20000],
            ['label' => 'Weight Charge', 'amount_paise' => 4000],
        ], $quote->breakdown);
    }

    public function test_applies_a_flat_peak_hour_surcharge(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_PEAK_HOUR,
            'amount_paise' => 500,
            'percentage' => null,
        ]);

        $quote = $this->engine->quote($this->baseInput());

        $this->assertSame(10500, $quote->totalAmountPaise);
        $this->assertContains(['label' => 'Peak Hour Surcharge', 'amount_paise' => 500], $quote->breakdown);
    }

    public function test_applies_a_percentage_based_peak_hour_surcharge_on_the_base_and_weight_subtotal(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_PEAK_HOUR,
            'amount_paise' => null,
            'percentage' => 50,
        ]);

        $quote = $this->engine->quote($this->baseInput());

        $this->assertSame(15000, $quote->totalAmountPaise);
        $this->assertContains(['label' => 'Peak Hour Surcharge', 'amount_paise' => 5000], $quote->breakdown);
    }

    public function test_applies_a_percentage_coupon_discount_capped_by_max_discount_paise(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        Coupon::factory()->create([
            'code' => 'HALFOFF',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'value' => 50,
            'max_discount_paise' => 1000,
        ]);

        $quote = $this->engine->quote($this->baseInput(['coupon_code' => 'HALFOFF']));

        $this->assertSame(9000, $quote->totalAmountPaise);
        $this->assertContains(['label' => 'Coupon Discount', 'amount_paise' => -1000], $quote->breakdown);
    }

    public function test_applies_a_flat_coupon_discount_converted_from_rupees_to_paise(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        Coupon::factory()->create([
            'code' => 'FLAT20',
            'discount_type' => Coupon::TYPE_FLAT,
            'value' => 20,
        ]);

        $quote = $this->engine->quote($this->baseInput(['coupon_code' => 'FLAT20']));

        $this->assertSame(8000, $quote->totalAmountPaise);
        $this->assertContains(['label' => 'Coupon Discount', 'amount_paise' => -2000], $quote->breakdown);
    }

    public function test_ignores_an_inactive_or_unknown_coupon_code(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        Coupon::factory()->create([
            'code' => 'EXPIRED',
            'is_active' => false,
        ]);

        $quote = $this->engine->quote($this->baseInput(['coupon_code' => 'EXPIRED']));

        $this->assertSame(10000, $quote->totalAmountPaise);
        $this->assertCount(1, $quote->breakdown);
    }

    public function test_applies_a_percentage_platform_fee_after_discount(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_PLATFORM_FEE,
            'amount_paise' => null,
            'percentage' => 5,
        ]);

        $quote = $this->engine->quote($this->baseInput());

        $this->assertSame(10500, $quote->totalAmountPaise);
        $this->assertContains(['label' => 'Platform Fee', 'amount_paise' => 500], $quote->breakdown);
    }

    public function test_applies_a_percentage_tax_on_the_discounted_subtotal_plus_platform_fee(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_TAX,
            'amount_paise' => null,
            'percentage' => 18,
        ]);

        $quote = $this->engine->quote($this->baseInput());

        $this->assertSame(11800, $quote->totalAmountPaise);
        $this->assertContains(['label' => 'Tax', 'amount_paise' => 1800], $quote->breakdown);
    }

    public function test_computes_the_full_breakdown_with_every_rule_type_and_a_coupon(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_WEIGHT_SLAB,
            'min_weight_grams' => 0,
            'max_weight_grams' => 1000,
            'amount_paise' => 2000,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_PEAK_HOUR,
            'amount_paise' => null,
            'percentage' => 10,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_PLATFORM_FEE,
            'amount_paise' => 500,
            'percentage' => null,
        ]);
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_TAX,
            'amount_paise' => null,
            'percentage' => 18,
        ]);
        Coupon::factory()->create([
            'code' => 'SAVE10',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'value' => 10,
            'max_discount_paise' => 2000,
        ]);

        // quantity 2: base 20000, weight 4000 -> peak 10% of 24000 = 2400 -> subtotal 26400
        // coupon 10% of 26400 = 2640, capped at 2000 -> discounted subtotal 24400
        // platform fee flat 500 -> taxable 24900 -> tax 18% = 4482 -> total 29382
        $quote = $this->engine->quote($this->baseInput(['quantity' => 2, 'coupon_code' => 'SAVE10']));

        $this->assertSame(29382, $quote->totalAmountPaise);
        $this->assertSame([
            ['label' => 'Base Fare', 'amount_paise' => 20000],
            ['label' => 'Weight Charge', 'amount_paise' => 4000],
            ['label' => 'Peak Hour Surcharge', 'amount_paise' => 2400],
            ['label' => 'Coupon Discount', 'amount_paise' => -2000],
            ['label' => 'Platform Fee', 'amount_paise' => 500],
            ['label' => 'Tax', 'amount_paise' => 4482],
        ], $quote->breakdown);
    }

    public function test_rejects_an_unknown_weight_slab(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->engine->quote($this->baseInput(['weight_slab' => 'not_a_real_slab']));
    }

    public function test_verify_quote_token_round_trips_a_valid_token(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);

        $quote = $this->engine->quote($this->baseInput());
        $payload = $this->engine->verifyQuoteToken($quote->quoteToken);

        $this->assertSame($this->route->id, $payload['route_id']);
        $this->assertSame($this->schedule->id, $payload['route_schedule_id']);
        $this->assertSame(10000, $payload['total_amount_paise']);
    }

    public function test_verify_quote_token_rejects_a_tampered_token(): void
    {
        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);

        $quote = $this->engine->quote($this->baseInput());

        $this->expectException(InvalidQuoteTokenException::class);
        $this->engine->verifyQuoteToken($quote->quoteToken.'tampered');
    }

    public function test_verify_quote_token_rejects_an_expired_token(): void
    {
        config(['pricing.quote_token_ttl_minutes' => 1]);

        PricingRule::factory()->for($this->route, 'route')->create([
            'rule_type' => PricingRule::TYPE_BASE,
            'amount_paise' => 10000,
        ]);

        $quote = $this->engine->quote($this->baseInput());

        $this->travel(2)->minutes();

        $this->expectException(InvalidQuoteTokenException::class);
        $this->engine->verifyQuoteToken($quote->quoteToken);
    }
}
