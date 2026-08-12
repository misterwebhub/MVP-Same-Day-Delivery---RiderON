<?php

namespace Tests\Unit;

use App\Exceptions\OtpAlreadyVerifiedException;
use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpLockedException;
use App\Exceptions\OtpResendCooldownException;
use App\Exceptions\OtpResendLimitException;
use App\Models\OtpVerification;
use App\Models\OtpVerificationLog;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\SmsProvider;
use App\Services\Sms\SmsSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    private OtpService $service;

    private SmsProvider $smsProvider;

    /** Captured from the outgoing SMS body — the only place the plaintext OTP is ever exposed. */
    private ?string $capturedOtp = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsProvider = \Mockery::mock(SmsProvider::class);
        $this->app->instance(SmsProvider::class, $this->smsProvider);
        $this->service = $this->app->make(OtpService::class);
    }

    private function expectSmsSent(): void
    {
        $this->smsProvider->shouldReceive('send')
            ->once()
            ->withArgs(function (string $phone, string $message): bool {
                preg_match('/OTP is (\d+)\./', $message, $matches);
                $this->capturedOtp = $matches[1] ?? null;

                return true;
            })
            ->andReturn(new SmsSendResult(true, 'mock_id', null));
    }

    public function test_generate_creates_a_hashed_otp_and_sends_it_via_the_sms_provider(): void
    {
        $this->expectSmsSent();

        $user = User::factory()->create();

        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);

        $this->assertDatabaseHas('otp_verifications', [
            'id' => $verification->id,
            'phone' => $user->phone,
            'purpose' => OtpVerification::PURPOSE_LOGIN,
        ]);
        $this->assertNotNull($this->capturedOtp);
        $this->assertNotSame($this->capturedOtp, $verification->otp_hash);
    }

    public function test_verify_succeeds_with_the_correct_otp(): void
    {
        $this->expectSmsSent();

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);

        $result = $this->service->verify($verification->id, $this->capturedOtp, OtpVerificationLog::ATTEMPTED_BY_CUSTOMER, $user->id);

        $this->assertNotNull($result->verified_at);
        $this->assertDatabaseHas('otp_verification_logs', [
            'otp_verification_id' => $verification->id,
            'result' => OtpVerificationLog::RESULT_SUCCESS,
        ]);
    }

    public function test_verify_rejects_an_incorrect_otp_and_increments_attempt_count(): void
    {
        $this->expectSmsSent();

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);
        $wrongOtp = $this->capturedOtp === '0000' ? '1111' : '0000';

        $this->expectException(OtpInvalidException::class);

        try {
            $this->service->verify($verification->id, $wrongOtp, OtpVerificationLog::ATTEMPTED_BY_CUSTOMER, $user->id);
        } finally {
            $this->assertSame(1, $verification->fresh()->attempt_count);
            $this->assertDatabaseHas('otp_verification_logs', [
                'otp_verification_id' => $verification->id,
                'result' => OtpVerificationLog::RESULT_INVALID,
            ]);
        }
    }

    public function test_verify_locks_the_otp_after_max_attempts_are_exhausted(): void
    {
        $this->expectSmsSent();
        config(['otp.max_attempts' => 2]);

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);
        $verification->forceFill(['max_attempts' => 2])->save();
        $wrongOtp = $this->capturedOtp === '0000' ? '1111' : '0000';

        try {
            $this->service->verify($verification->id, $wrongOtp, OtpVerificationLog::ATTEMPTED_BY_CUSTOMER, $user->id);
        } catch (OtpInvalidException) {
            // expected on the first bad attempt
        }

        $this->expectException(OtpLockedException::class);
        $this->service->verify($verification->id, $wrongOtp, OtpVerificationLog::ATTEMPTED_BY_CUSTOMER, $user->id);
    }

    public function test_verify_rejects_an_expired_otp(): void
    {
        $this->expectSmsSent();

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id, now()->subMinute());

        $this->expectException(OtpExpiredException::class);
        $this->service->verify($verification->id, $this->capturedOtp, OtpVerificationLog::ATTEMPTED_BY_CUSTOMER, $user->id);
    }

    public function test_verify_rejects_an_already_verified_otp(): void
    {
        $this->expectSmsSent();

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);
        $verification->forceFill(['verified_at' => now()])->save();

        $this->expectException(OtpAlreadyVerifiedException::class);
        $this->service->verify($verification->id, $this->capturedOtp, OtpVerificationLog::ATTEMPTED_BY_CUSTOMER, $user->id);
    }

    public function test_resend_respects_the_cooldown_window(): void
    {
        $this->expectSmsSent();

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);
        $verification->forceFill(['last_sent_at' => now()])->save();

        $this->expectException(OtpResendCooldownException::class);
        $this->service->resend($verification->fresh());
    }

    public function test_resend_enforces_the_max_resend_limit(): void
    {
        $this->expectSmsSent();
        config(['otp.max_resends' => 1]);

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);
        $verification->forceFill(['resend_count' => 1, 'last_sent_at' => now()->subHour()])->save();

        $this->expectException(OtpResendLimitException::class);
        $this->service->resend($verification->fresh());
    }

    public function test_resend_issues_a_new_otp_and_resets_attempt_state(): void
    {
        $this->expectSmsSent(); // initial generate()

        $user = User::factory()->create();
        $verification = $this->service->generate(OtpVerification::PURPOSE_LOGIN, $user->phone, null, $user->id);
        $originalHash = $verification->otp_hash;
        $verification->forceFill([
            'attempt_count' => 3,
            'last_sent_at' => now()->subHour(),
        ])->save();

        $this->expectSmsSent(); // resend()
        $resent = $this->service->resend($verification->fresh());

        $this->assertSame(0, $resent->attempt_count);
        $this->assertSame(1, $resent->resend_count);
        $this->assertNotSame($originalHash, $resent->otp_hash);
    }
}
