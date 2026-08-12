<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * Real railway stations for the Phase 1 Kanpur <-> Lucknow corridor
 * (docs/00, docs/02 line 28 names these exact stations/codes).
 */
class StationSeeder extends Seeder
{
    public function run(): void
    {
        $kanpur = City::query()->where('name', 'Kanpur')->firstOrFail();
        $lucknow = City::query()->where('name', 'Lucknow')->firstOrFail();

        Station::query()->updateOrCreate(
            ['code' => 'CNB'],
            [
                'city_id' => $kanpur->id,
                'name' => 'Kanpur Central',
                'type' => Station::TYPE_RAILWAY,
                'latitude' => 26.4499,
                'longitude' => 80.3319,
                'address' => 'Kanpur Central Railway Station, Kanpur, Uttar Pradesh 208001',
                'is_active' => true,
            ],
        );

        Station::query()->updateOrCreate(
            ['code' => 'LKO'],
            [
                'city_id' => $lucknow->id,
                'name' => 'Lucknow Charbagh',
                'type' => Station::TYPE_RAILWAY,
                'latitude' => 26.8302,
                'longitude' => 80.9199,
                'address' => 'Lucknow Charbagh Railway Station, Lucknow, Uttar Pradesh 226004',
                'is_active' => true,
            ],
        );
    }
}
