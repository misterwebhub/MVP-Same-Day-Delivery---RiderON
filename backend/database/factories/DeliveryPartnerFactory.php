<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\DeliveryPartner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryPartner>
 */
class DeliveryPartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => User::ROLE_PARTNER]),
            'partner_code' => strtoupper('RP'.fake()->unique()->numerify('####')),
            'vehicle_type' => DeliveryPartner::VEHICLE_TRAIN,
            'verification_status' => DeliveryPartner::VERIFICATION_VERIFIED,
            'is_active' => true,
            'rating_avg' => 4.5,
            'completed_deliveries_count' => 0,
            'current_home_city_id' => City::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['verification_status' => DeliveryPartner::VERIFICATION_PENDING]);
    }
}
