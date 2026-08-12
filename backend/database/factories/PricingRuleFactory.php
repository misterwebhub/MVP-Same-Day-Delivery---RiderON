<?php

namespace Database\Factories;

use App\Models\PricingRule;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PricingRule>
 */
class PricingRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'route_id' => Route::factory(),
            'rule_type' => PricingRule::TYPE_BASE,
            'min_weight_grams' => null,
            'max_weight_grams' => null,
            'amount_paise' => 10000,
            'percentage' => null,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
            'is_active' => true,
        ];
    }
}
