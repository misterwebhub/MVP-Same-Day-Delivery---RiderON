<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dev/staging admin account for the Filament panel (Task #17). The seeded
 * password is a placeholder only — must be rotated before any shared or
 * production deployment.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['phone' => '9999900000'],
            [
                'name' => 'RiderON Admin',
                'email' => 'admin@rideron.app',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'password' => Hash::make('ChangeMe123!'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ],
        );
    }
}
