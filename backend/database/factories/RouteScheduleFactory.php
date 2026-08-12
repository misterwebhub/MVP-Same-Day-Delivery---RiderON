<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RouteSchedule>
 */
class RouteScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'route_id' => Route::factory(),
            'departure_time' => '08:00:00',
            'arrival_time' => '14:00:00',
            'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
            'booking_cutoff_minutes_before' => 60,
            'is_active' => true,
        ];
    }
}
