<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('COUPON???')),
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'value' => 10,
            'max_discount_paise' => null,
            'min_order_amount_paise' => null,
            'usage_limit_total' => 1000,
            'usage_limit_per_user' => 1,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
