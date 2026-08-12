<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * Kanpur <-> Lucknow route, seeded as two directional rows per docs/02
 * ("Kanpur->Lucknow and Lucknow->Kanpur are two separate rows"). Road
 * distance between the two stations is ~80km; estimated_duration_minutes
 * covers door-to-door handling time, not just raw transit time.
 */
class RouteSeeder extends Seeder
{
    private const ALL_DAYS = [1, 2, 3, 4, 5, 6, 7];

    private const DEPARTURES = [
        ['departure' => '07:00:00', 'arrival' => '09:30:00'],
        ['departure' => '13:00:00', 'arrival' => '15:30:00'],
        ['departure' => '19:00:00', 'arrival' => '21:30:00'],
    ];

    public function run(): void
    {
        $kanpurCentral = Station::query()->where('code', 'CNB')->firstOrFail();
        $lucknowCharbagh = Station::query()->where('code', 'LKO')->firstOrFail();

        $kanpurToLucknow = Route::query()->updateOrCreate(
            ['origin_station_id' => $kanpurCentral->id, 'destination_station_id' => $lucknowCharbagh->id],
            [
                'distance_km' => 80.00,
                'estimated_duration_minutes' => 150,
                'cutoff_time' => '18:00:00',
                'max_parcels_per_schedule' => 50,
                'waiting_time_minutes' => 30,
                'is_active' => true,
            ],
        );

        $lucknowToKanpur = Route::query()->updateOrCreate(
            ['origin_station_id' => $lucknowCharbagh->id, 'destination_station_id' => $kanpurCentral->id],
            [
                'distance_km' => 80.00,
                'estimated_duration_minutes' => 150,
                'cutoff_time' => '18:00:00',
                'max_parcels_per_schedule' => 50,
                'waiting_time_minutes' => 30,
                'is_active' => true,
            ],
        );

        foreach ([$kanpurToLucknow, $lucknowToKanpur] as $route) {
            foreach (self::DEPARTURES as $slot) {
                RouteSchedule::query()->updateOrCreate(
                    [
                        'route_id' => $route->id,
                        'departure_time' => $slot['departure'],
                    ],
                    [
                        'arrival_time' => $slot['arrival'],
                        'days_of_week' => self::ALL_DAYS,
                        'booking_cutoff_minutes_before' => 60,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
