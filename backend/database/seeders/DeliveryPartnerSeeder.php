<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\DeliveryPartner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dev/staging delivery partner account for the partner app (Task #32),
 * mirroring AdminUserSeeder's pattern. Verified + active + home-based in
 * Kanpur (the seeded launch corridor's origin city, docs/00) so real
 * PartnerAssignmentService auto-assignment picks it up for orders booked
 * on the Kanpur -> Lucknow test route during E2E verification.
 * The seeded password is a placeholder only — must be rotated before any
 * shared or production deployment.
 */
class DeliveryPartnerSeeder extends Seeder
{
    public function run(): void
    {
        $homeCity = City::query()->where('name', 'Kanpur')->first();

        $user = User::query()->updateOrCreate(
            ['phone' => '9999900001'],
            [
                'name' => 'Test Partner',
                'email' => 'partner1@rideron.app',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'password' => Hash::make('ChangeMe123!'),
                'role' => User::ROLE_PARTNER,
                'status' => User::STATUS_ACTIVE,
            ],
        );

        DeliveryPartner::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'partner_code' => 'RP0001',
                'vehicle_type' => DeliveryPartner::VEHICLE_TRAIN,
                'verification_status' => DeliveryPartner::VERIFICATION_VERIFIED,
                'is_active' => true,
                'rating_avg' => 4.8,
                'completed_deliveries_count' => 0,
                'current_home_city_id' => $homeCity?->id,
            ],
        );
    }
}
