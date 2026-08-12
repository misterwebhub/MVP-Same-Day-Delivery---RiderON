<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\Sms\SmsProvider;
use App\Services\Sms\SmsSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class AuthOtpLoginTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    private ?string $capturedOtp = null;

    private SmsProvider $smsProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsProvider = \Mockery::mock(SmsProvider::class);
        $this->app->instance(SmsProvider::class, $this->smsProvider);
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

    public function test_a_new_phone_number_can_request_and_verify_an_otp_to_log_in(): void
    {
        $this->expectSmsSent();
        $phone = '9876543210';

        $requestResponse = $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone, 'purpose' => 'login']);
        $requestResponse->assertOk();

        $this->assertNotNull($this->capturedOtp);
        $this->assertDatabaseHas('users', ['phone' => $phone]);

        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'otp' => $this->capturedOtp,
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJsonPath('data.is_new_user', true);
        $this->assertNotEmpty($verifyResponse->json('data.access_token'));
        $this->assertNotEmpty($verifyResponse->json('data.refresh_token'));

        $user = User::query()->where('phone', $phone)->firstOrFail();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_verify_rejects_an_incorrect_otp_via_the_real_http_endpoint(): void
    {
        $this->expectSmsSent();
        $phone = '9876543211';

        $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone, 'purpose' => 'login'])->assertOk();
        $wrongOtp = $this->capturedOtp === '0000' ? '1111' : '0000';

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'otp' => $wrongOtp,
        ]);

        $response->assertStatus(422);
        $user = User::query()->where('phone', $phone)->firstOrFail();
        $this->assertNull($user->phone_verified_at);
    }

    public function test_request_otp_is_rejected_for_a_suspended_account(): void
    {
        $phone = '9876543212';
        User::factory()->create(['phone' => $phone, 'status' => User::STATUS_SUSPENDED]);

        $response = $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone, 'purpose' => 'login']);

        $response->assertStatus(403);
    }

    public function test_an_authenticated_request_can_use_the_issued_access_token(): void
    {
        $this->expectSmsSent();
        $phone = '9876543213';

        $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone, 'purpose' => 'login'])->assertOk();
        $verifyResponse = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $phone,
            'otp' => $this->capturedOtp,
        ]);

        $token = $verifyResponse->json('data.access_token');

        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/user');

        $meResponse->assertOk();
        $meResponse->assertJsonPath('phone', $phone);
    }

    public function test_verify_fails_once_the_otp_has_already_been_used(): void
    {
        $this->expectSmsSent();
        $phone = '9876543214';

        $this->postJson('/api/v1/auth/otp/request', ['phone' => $phone, 'purpose' => 'login'])->assertOk();
        $otp = $this->capturedOtp;

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'otp' => $otp])->assertOk();

        $replay = $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'otp' => $otp]);

        $replay->assertStatus(422);
        $this->assertSame(
            1,
            OtpVerification::query()->where('phone', $phone)->whereNotNull('verified_at')->count()
        );
    }
}
