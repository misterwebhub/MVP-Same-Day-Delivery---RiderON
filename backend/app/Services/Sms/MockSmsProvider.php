<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dev/test double for SmsProvider — logs the message (including OTP codes)
 * to storage/logs instead of calling out, so a developer can retrieve it
 * without a real SMS account. Never used when SMS_DRIVER=msg91.
 */
class MockSmsProvider implements SmsProvider
{
    public function send(string $toPhone, string $message): SmsSendResult
    {
        Log::info('[MockSmsProvider] SMS not actually sent (SMS_DRIVER=mock)', [
            'to' => $toPhone,
            'message' => $message,
        ]);

        return new SmsSendResult(true, 'mock_'.Str::random(12), null);
    }
}
