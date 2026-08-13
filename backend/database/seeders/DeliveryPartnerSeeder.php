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
    /**
     * Three dev/test partners (not one) so admin "assign partner" can be
     * exercised realistically: the same-day-busy exclusion in
     * OrderRepository::eligiblePartners() means a single partner already
     * tied to one order makes every other same-day order unassignable in
     * this dev DB. More seeded partners means real testing of that
     * dropdown instead of it going empty after the first assignment.
     */
    private const PARTNERS = [
        ['phone' => '9999900001', 'name' => 'Test Partner', 'email' => 'partner1@rideron.app', 'code' => 'RP0001'],
        ['phone' => '9999900002', 'name' => 'Test Partner Two', 'email' => 'partner2@rideron.app', 'code' => 'RP0002'],
        ['phone' => '9999900003', 'name' => 'Test Partner Three', 'email' => 'partner3@rideron.app', 'code' => 'RP0003'],
    ];

    public function run(): void
    {
        $homeCity = City::query()->where('name', 'Kanpur')->first();

        foreach (self::PARTNERS as $partner) {
            $user = User::query()->updateOrCreate(
                ['phone' => $partner['phone']],
                [
                    'name' => $partner['name'],
                    'email' => $partner['email'],
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
                    'partner_code' => $partner['code'],
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
}
