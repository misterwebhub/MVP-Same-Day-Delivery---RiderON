<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Station>
 */
class StationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => fake()->unique()->city().' Junction',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'type' => 'railway',
            'is_active' => true,
        ];
    }
}
