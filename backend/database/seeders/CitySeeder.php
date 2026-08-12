<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

/**
 * Phase 1 launch corridor per docs/00: Kanpur <-> Lucknow.
 */
class CitySeeder extends Seeder
{
    public function run(): void
    {
        City::query()->updateOrCreate(
            ['name' => 'Kanpur', 'state' => 'Uttar Pradesh'],
            ['is_active' => true],
        );

        City::query()->updateOrCreate(
            ['name' => 'Lucknow', 'state' => 'Uttar Pradesh'],
            ['is_active' => true],
        );
    }
}
