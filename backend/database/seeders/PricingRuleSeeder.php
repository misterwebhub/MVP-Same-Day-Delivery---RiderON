<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Global (route_id = null) default pricing so any future route inherits
 * sensible pricing until it gets route-specific overrides (PricingEngine
 * prefers route-specific rules over global ones of the same rule_type).
 * Weight-slab ranges must match config('pricing.weight_slab_grams').
 */
class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $effectiveFrom = Carbon::now()->subDay();

        PricingRule::query()->updateOrCreate(
            ['route_id' => null, 'rule_type' => PricingRule::TYPE_BASE],
            [
                'amount_paise' => 5000,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        $weightSlabs = [
            ['min' => 0, 'max' => 1000, 'amount_paise' => 8000],
            ['min' => 1001, 'max' => 3000, 'amount_paise' => 15000],
            ['min' => 3001, 'max' => 5000, 'amount_paise' => 22000],
            ['min' => 5001, 'max' => 10000, 'amount_paise' => 35000],
        ];

        foreach ($weightSlabs as $slab) {
            PricingRule::query()->updateOrCreate(
                [
                    'route_id' => null,
                    'rule_type' => PricingRule::TYPE_WEIGHT_SLAB,
                    'min_weight_grams' => $slab['min'],
                    'max_weight_grams' => $slab['max'],
                ],
                [
                    'amount_paise' => $slab['amount_paise'],
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'is_active' => true,
                ],
            );
        }

        PricingRule::query()->updateOrCreate(
            ['route_id' => null, 'rule_type' => PricingRule::TYPE_PLATFORM_FEE],
            [
                'percentage' => 2.00,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_active' => true,
            ],
        );

        PricingRule::query()->updateOrCreate(
            ['route_id' => null, 'rule_type' => PricingRule::TYPE_TAX],
            [
                'percentage' => 5.00,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_active' => true,
            ],
        );
    }
}
