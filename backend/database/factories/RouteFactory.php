<?php

namespace Database\Factories;

use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Route>
 */
class RouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'origin_station_id' => Station::factory(),
            'destination_station_id' => Station::factory(),
            'distance_km' => fake()->randomFloat(2, 50, 500),
            'estimated_duration_minutes' => fake()->numberBetween(60, 600),
            'cutoff_time' => '18:00:00',
            'max_parcels_per_schedule' => 50,
            'waiting_time_minutes' => 30,
            'is_active' => true,
        ];
    }
}
