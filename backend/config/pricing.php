<?php

return [

    'quote_token_ttl_minutes' => (int) env('QUOTE_TOKEN_TTL_MINUTES', 10),

    /**
     * Representative upper-bound grams per weight slab, used to match a
     * parcel's slab against pricing_rules.min_weight_grams/max_weight_grams
     * ranges. Kept here (not hard-coded in PricingEngine) so it stays a
     * single, documented source of truth alongside the other pricing knobs.
     */
    'weight_slab_grams' => [
        'upto_100g' => 100,
        'upto_1kg' => 1000,
        'upto_2kg' => 2000,
    ],

    /**
     * Flat door-pickup add-on fee (paise), charged when the customer opts
     * into door pickup at booking time. A coupon with waives_door_pickup can
     * zero this out specifically without discounting the rest of the order.
     */
    'door_pickup_fee_paise' => (int) env('DOOR_PICKUP_FEE_PAISE', 8000),

    /**
     * Share of an order's total_amount_paise a delivery partner earns once
     * the order reaches COMPLETED. Used by PartnerEarningsController — kept
     * here (not hard-coded) since it's a real business rule that will move
     * to a per-partner or per-route override later, per docs/06.
     */
    'partner_commission_percent' => (int) env('PARTNER_COMMISSION_PERCENT', 70),

];
