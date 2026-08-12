<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockPushProvider implements PushProvider
{
    public function send(string $fcmToken, string $title, string $body, array $data = []): PushSendResult
    {
        Log::info('[MockPushProvider] Push not actually sent (PUSH_DRIVER=mock)', [
            'fcm_token' => $fcmToken,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        return new PushSendResult(true, 'mock_'.Str::random(12), null);
    }
}
