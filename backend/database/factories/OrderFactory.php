<?php

namespace Database\Factories;

use App\Constants\OrderStatus;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $route = Route::factory();

        return [
            'booking_reference' => strtoupper('RO'.fake()->unique()->numerify('########')),
            'customer_id' => User::factory()->state(['role' => User::ROLE_CUSTOMER]),
            'route_id' => $route,
            'route_schedule_id' => RouteSchedule::factory()->for($route, 'route'),
            'partner_id' => null,
            'status' => OrderStatus::BOOKED,
            'booking_date' => now()->addDay()->toDateString(),
            'sender_name' => fake()->name(),
            'sender_phone' => fake()->numerify('9#########'),
            'receiver_name' => fake()->name(),
            'receiver_phone' => fake()->numerify('9#########'),
            'price_breakdown' => [['label' => 'Base Fare', 'amount_paise' => 15000]],
            'total_amount_paise' => 15000,
            'currency' => 'INR',
        ];
    }
}
