<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AccountSuspendedException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\OtpExpiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\PartnerLoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\OtpVerification;
use App\Models\OtpVerificationLog;
use App\Models\User;
use App\Services\Auth\TokenService;
use App\Services\Otp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly TokenService $tokenService,
    ) {
    }

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        $user = User::query()->firstOrCreate(
            ['phone' => $phone],
            ['role' => User::ROLE_CUSTOMER, 'status' => User::STATUS_ACTIVE],
        );

        if ($user->status !== User::STATUS_ACTIVE) {
            throw new AccountSuspendedException();
        }

        // In dev/staging (SMS_DRIVER=mock) no real SMS goes out, so the plaintext
        // OTP is echoed back in the response for local testing convenience — lets
        // a dev on a physical device/Expo Go log in without tailing the Laravel
        // log. Never happens when a real SMS provider is configured.
        $isMockSms = config('services.sms_driver') === 'mock';

        [$verification, $plainOtp] = $isMockSms
            ? $this->otpService->generateWithPlainOtp(
                purpose: OtpVerification::PURPOSE_LOGIN,
                phone: $phone,
                orderId: null,
                userId: $user->id,
            )
            : [$this->otpService->generate(
                purpose: OtpVerification::PURPOSE_LOGIN,
                phone: $phone,
                orderId: null,
                userId: $user->id,
            ), null];

        return $this->success([
            'phone' => $phone,
            'expires_at' => $verification->expires_at->toIso8601String(),
            ...($isMockSms ? ['dev_otp' => $plainOtp] : []),
        ], 'OTP sent.');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        $user = User::query()->where('phone', $phone)->firstOrFail();

        if ($user->status !== User::STATUS_ACTIVE) {
            throw new AccountSuspendedException();
        }

        $verification = OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', OtpVerification::PURPOSE_LOGIN)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $verification) {
            throw new OtpExpiredException();
        }

        $attemptedByType = $user->role === User::ROLE_PARTNER
            ? OtpVerificationLog::ATTEMPTED_BY_PARTNER
            : OtpVerificationLog::ATTEMPTED_BY_CUSTOMER;

        $this->otpService->verify(
            otpVerificationId: $verification->id,
            inputOtp: $request->string('otp')->toString(),
            attemptedByType: $attemptedByType,
            attemptedById: $user->id,
            ipAddress: $request->ip(),
        );

        $isNewUser = $user->phone_verified_at === null;

        $user->forceFill([
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        $tokens = $this->tokenService->issue($user, $user->role);

        return $this->success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'is_new_user' => $isNewUser,
        ], 'Login successful.');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokens = $this->tokenService->rotate($request->string('refresh_token')->toString());

        return $this->success($tokens, 'Token refreshed.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokenService->revokeCurrent($request->user());

        return $this->success(null, 'Logged out.');
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email'),
        ])->save();

        return $this->success($user->only(['id', 'name', 'email', 'phone', 'role']), 'Profile updated.');
    }

    public function partnerLogin(PartnerLoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('phone', $request->string('phone')->toString())
            ->where('role', User::ROLE_PARTNER)
            ->first();

        if (! $user || ! $user->password || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw new AccountSuspendedException();
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $tokens = $this->tokenService->issue($user, User::ROLE_PARTNER);

        return $this->success($tokens, 'Login successful.');
    }
}
