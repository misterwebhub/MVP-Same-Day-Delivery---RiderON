<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidRefreshTokenException;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Issues/rotates/revokes the RiderON access+refresh token pair: a short-lived
 * Sanctum personal access token (see config/sanctum.php "expiration") plus a
 * longer-lived opaque refresh token, hashed at rest in `refresh_tokens`.
 */
class TokenService
{
    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function issue(User $user, string $ability): array
    {
        $accessToken = $user->createToken('access', [$ability]);
        $plainRefreshToken = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainRefreshToken),
            'personal_access_token_id' => $accessToken->accessToken->id,
            'expires_at' => now()->addDays((int) config('auth_tokens.refresh_token_ttl_days')),
        ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $plainRefreshToken,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function rotate(string $plainRefreshToken): array
    {
        $refreshToken = RefreshToken::query()
            ->where('token_hash', hash('sha256', $plainRefreshToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $refreshToken) {
            throw new InvalidRefreshTokenException();
        }

        $user = $refreshToken->user;
        $ability = $refreshToken->personalAccessToken?->abilities[0] ?? $user->role;

        DB::transaction(function () use ($refreshToken) {
            $refreshToken->forceFill(['revoked_at' => now()])->save();
            $refreshToken->personalAccessToken?->delete();
        });

        return $this->issue($user, $ability);
    }

    public function revokeCurrent(User $user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken === null) {
            return;
        }

        RefreshToken::query()
            ->where('personal_access_token_id', $currentToken->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $currentToken->delete();
    }
}
