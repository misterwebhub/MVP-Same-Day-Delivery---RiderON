<?php

namespace App\Services\Otp;

use App\Exceptions\OtpAlreadyVerifiedException;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpLockedException;
use App\Exceptions\OtpResendCooldownException;
use App\Exceptions\OtpResendLimitException;
use App\Models\OtpVerification;
use App\Models\OtpVerificationLog;
use App\Services\Sms\SmsProvider;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private readonly SmsProvider $smsProvider)
    {
    }

    public function generate(
        string $purpose,
        string $phone,
        ?int $orderId,
        ?int $userId,
        ?CarbonInterface $expiresAt = null,
    ): OtpVerification {
        return $this->generateInternal($purpose, $phone, $orderId, $userId, $expiresAt)[0];
    }

    /**
     * Same as generate(), but also returns the transient plaintext OTP —
     * intended only for trusted server-side callers (e.g. the admin panel's
     * "generate OTP for testing" action) that need to display the code
     * without waiting on SMS delivery. The plaintext is never persisted.
     *
     * @return array{0: OtpVerification, 1: string}
     */
    public function generateWithPlainOtp(
        string $purpose,
        string $phone,
        ?int $orderId,
        ?int $userId,
        ?CarbonInterface $expiresAt = null,
    ): array {
        return $this->generateInternal($purpose, $phone, $orderId, $userId, $expiresAt);
    }

    public function resend(OtpVerification $otp, ?CarbonInterface $expiresAt = null): OtpVerification
    {
        return $this->resendInternal($otp, $expiresAt)[0];
    }

    /**
     * Same as resend(), but also returns the transient plaintext OTP — see
     * generateWithPlainOtp() for why this exists. $bypassLimits skips the
     * resend-count/cooldown guards (still used by resend_count bookkeeping,
     * just not enforced) — only intended for the admin "generate for
     * testing" action, never for the customer-facing resend endpoint.
     *
     * @return array{0: OtpVerification, 1: string}
     */
    public function resendWithPlainOtp(OtpVerification $otp, ?CarbonInterface $expiresAt = null, bool $bypassLimits = false): array
    {
        return $this->resendInternal($otp, $expiresAt, $bypassLimits);
    }

    /**
     * Returns the currently-valid plaintext OTP for in-app display (docs/05
     * "show the code in the order screen, not just SMS"), or null if there
     * is nothing to show — already verified, expired, or the ephemeral
     * cache entry is gone (e.g. cache store was flushed, or this is an old
     * OTP generated before this cache existed). Callers must treat null as
     * "fall back to SMS / offer resend", never as an error.
     */
    public function peekCachedPlainOtp(OtpVerification $otp): ?string
    {
        if ($otp->verified_at !== null || $otp->expires_at->isPast()) {
            return null;
        }

        return Cache::get($this->plainOtpCacheKey($otp));
    }

    /**
     * @return array{0: OtpVerification, 1: string}
     */
    private function generateInternal(
        string $purpose,
        string $phone,
        ?int $orderId,
        ?int $userId,
        ?CarbonInterface $expiresAt = null,
    ): array {
        $otp = $this->generateNumericOtp();
        $now = Carbon::now();

        $verification = OtpVerification::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'purpose' => $purpose,
            'phone' => $phone,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $expiresAt ?? $now->clone()->addMinutes((int) config('otp.expiry_minutes')),
            'max_attempts' => (int) config('otp.max_attempts'),
            'last_sent_at' => $now,
        ]);

        $this->dispatchSms($phone, $purpose, $otp);
        $this->cachePlainOtp($verification, $otp);

        return [$verification, $otp];
    }

    /**
     * @return array{0: OtpVerification, 1: string}
     */
    private function resendInternal(OtpVerification $otp, ?CarbonInterface $expiresAt = null, bool $bypassLimits = false): array
    {
        if ($otp->verified_at !== null) {
            throw new OtpAlreadyVerifiedException();
        }

        if (! $bypassLimits) {
            if ($otp->resend_count >= (int) config('otp.max_resends')) {
                throw new OtpResendLimitException();
            }

            $cooldownUntil = $otp->last_sent_at?->clone()->addSeconds((int) config('otp.resend_cooldown_seconds'));
            if ($cooldownUntil !== null && $cooldownUntil->isFuture()) {
                throw new OtpResendCooldownException($cooldownUntil);
            }
        }

        $newOtp = $this->generateNumericOtp();
        $now = Carbon::now();

        $otp->forceFill([
            'otp_hash' => Hash::make($newOtp),
            'expires_at' => $expiresAt ?? $now->clone()->addMinutes((int) config('otp.expiry_minutes')),
            'attempt_count' => 0,
            'locked_until' => null,
            'resend_count' => $otp->resend_count + 1,
            'last_sent_at' => $now,
        ])->save();

        $this->dispatchSms($otp->phone, $otp->purpose, $newOtp);
        $this->cachePlainOtp($otp, $newOtp);

        return [$otp, $newOtp];
    }

    public function verify(
        int $otpVerificationId,
        string $inputOtp,
        string $attemptedByType,
        int $attemptedById,
        ?string $ipAddress = null,
    ): OtpVerification {
        // Failure paths must still commit their attempt/lock/log writes, so the
        // exception is captured and thrown *after* the transaction closure returns
        // rather than from inside it (throwing inside would roll back those writes).
        $exceptionToThrow = null;

        $otp = DB::transaction(function () use (
            $otpVerificationId,
            $inputOtp,
            $attemptedByType,
            $attemptedById,
            $ipAddress,
            &$exceptionToThrow,
        ) {
            /** @var OtpVerification $otp */
            $otp = OtpVerification::query()->whereKey($otpVerificationId)->lockForUpdate()->firstOrFail();

            if ($otp->verified_at !== null) {
                $exceptionToThrow = new OtpAlreadyVerifiedException();

                return $otp;
            }

            if ($otp->locked_until !== null && $otp->locked_until->isFuture()) {
                $this->log($otp, $attemptedByType, $attemptedById, OtpVerificationLog::RESULT_LOCKED, $ipAddress);
                $exceptionToThrow = new OtpLockedException($otp->locked_until);

                return $otp;
            }

            if ($otp->expires_at->isPast()) {
                $this->log($otp, $attemptedByType, $attemptedById, OtpVerificationLog::RESULT_EXPIRED, $ipAddress);
                $exceptionToThrow = new OtpExpiredException();

                return $otp;
            }

            if (! Hash::check($inputOtp, $otp->otp_hash)) {
                $otp->attempt_count++;

                if ($otp->attempt_count >= $otp->max_attempts) {
                    $otp->locked_until = Carbon::now()->addMinutes((int) config('otp.lock_minutes'));
                    $otp->save();
                    $this->log($otp, $attemptedByType, $attemptedById, OtpVerificationLog::RESULT_LOCKED, $ipAddress);
                    $exceptionToThrow = new OtpLockedException($otp->locked_until);

                    return $otp;
                }

                $otp->save();
                $this->log($otp, $attemptedByType, $attemptedById, OtpVerificationLog::RESULT_INVALID, $ipAddress);
                $exceptionToThrow = new OtpInvalidException($otp->max_attempts - $otp->attempt_count);

                return $otp;
            }

            $otp->verified_at = Carbon::now();
            $otp->save();
            $this->log($otp, $attemptedByType, $attemptedById, OtpVerificationLog::RESULT_SUCCESS, $ipAddress);

            return $otp;
        });

        if ($exceptionToThrow !== null) {
            throw $exceptionToThrow;
        }

        $this->forgetCachedPlainOtp($otp);

        return $otp;
    }

    /**
     * Ephemeral in-app display cache for the plaintext OTP, keyed by the
     * verification row. TTL matches the OTP's own expiry, so the cached
     * value never outlives the code's real validity window — this is not
     * "persisting" the secret, it's a short-lived mirror of what's already
     * being sent via SMS, cleared immediately on successful verification.
     */
    private function cachePlainOtp(OtpVerification $otp, string $plainOtp): void
    {
        $ttlSeconds = max(1, (int) Carbon::now()->diffInSeconds($otp->expires_at, false));
        Cache::put($this->plainOtpCacheKey($otp), $plainOtp, $ttlSeconds);
    }

    private function forgetCachedPlainOtp(OtpVerification $otp): void
    {
        Cache::forget($this->plainOtpCacheKey($otp));
    }

    private function plainOtpCacheKey(OtpVerification $otp): string
    {
        return "otp_plain:{$otp->id}";
    }

    private function generateNumericOtp(): string
    {
        $length = (int) config('otp.length');
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9');

        return (string) random_int($min, $max);
    }

    private function dispatchSms(string $phone, string $purpose, string $otp): void
    {
        $this->smsProvider->send($phone, "Your RiderON {$purpose} OTP is {$otp}. Do not share it with anyone.");
    }

    private function log(
        OtpVerification $otp,
        string $attemptedByType,
        int $attemptedById,
        string $result,
        ?string $ipAddress,
    ): void {
        OtpVerificationLog::create([
            'otp_verification_id' => $otp->id,
            'attempted_by_type' => $attemptedByType,
            'attempted_by_id' => $attemptedById,
            'result' => $result,
            'ip_address' => $ipAddress,
        ]);
    }
}
