<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Central role gate for the admin panel, per docs/06's access rule:
 * ops/support may view orders and manage tickets, but pricing edits and
 * refund approval are admin-only.
 */
final class AdminAccess
{
    public static function user(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    public static function isAdmin(): bool
    {
        return static::user()?->role === User::ROLE_ADMIN;
    }

    public static function canManageMasterData(): bool
    {
        return in_array(static::user()?->role, [User::ROLE_ADMIN, User::ROLE_OPS], true);
    }

    public static function canManagePricing(): bool
    {
        return static::isAdmin();
    }

    public static function canApproveRefunds(): bool
    {
        return static::isAdmin();
    }

    public static function canManageOrders(): bool
    {
        return in_array(static::user()?->role, [User::ROLE_ADMIN, User::ROLE_OPS], true);
    }
}
