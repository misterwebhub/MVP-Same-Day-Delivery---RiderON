<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            StationSeeder::class,
            RouteSeeder::class,
            PricingRuleSeeder::class,
            ProhibitedItemSeeder::class,
            AdminUserSeeder::class,
            DeliveryPartnerSeeder::class,
        ]);
    }
}
