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

        // 1-100g ₹99, 101g-1kg ₹149, 1.1-2kg ₹249 — GST (18%, see TAX rule
        // below) is added on top of these by PricingEngine, not baked in.
        $weightSlabs = [
            ['min' => 0, 'max' => 100, 'amount_paise' => 9900],
            ['min' => 101, 'max' => 1000, 'amount_paise' => 14900],
            ['min' => 1001, 'max' => 2000, 'amount_paise' => 24900],
        ];

        // Old 3-5kg/5-10kg slabs are gone now that weight_slab_grams caps at
        // 2kg — deactivate rather than delete so historical orders that
        // still reference them keep resolving correctly if ever re-quoted.
        PricingRule::query()
            ->where('route_id', null)
            ->where('rule_type', PricingRule::TYPE_WEIGHT_SLAB)
            ->where('max_weight_grams', '>', 2000)
            ->update(['is_active' => false]);

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
                'percentage' => 18.00,
                'effective_from' => $effectiveFrom,
                'effective_to' => null,
                'is_active' => true,
            ],
        );
    }
}
