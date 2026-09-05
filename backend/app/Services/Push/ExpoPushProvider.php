<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Delivers via Expo's push API (https://exp.host/--/api/v2/push/send).
 * Chosen over raw FCM (see FcmProvider) as the default real driver because
 * both apps are Expo-managed: `expo-notifications` hands back an Expo push
 * token (ExponentPushToken[...]) with zero Firebase project setup needed on
 * either the client or server side. Set PUSH_DRIVER=expo once the mobile
 * apps register real device tokens (see NotificationController::registerDevice).
 */
class ExpoPushProvider implements PushProvider
{
    public function send(string $fcmToken, string $title, string $body, array $data = []): PushSendResult
    {
        if (! Str::startsWith($fcmToken, 'ExponentPushToken')) {
            return new PushSendResult(false, null, 'Not an Expo push token — skipping delivery.');
        }

        try {
            $response = Http::asJson()
                ->post('https://exp.host/--/api/v2/push/send', [
                    'to' => $fcmToken,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'sound' => 'default',
                    'priority' => 'high',
                ]);

            $ticket = $response->json('data');
            $status = is_array($ticket) ? ($ticket['status'] ?? null) : null;

            if ($response->successful() && $status === 'ok') {
                return new PushSendResult(true, $ticket['id'] ?? null, null);
            }

            $error = is_array($ticket) ? ($ticket['message'] ?? $response->body()) : $response->body();

            return new PushSendResult(false, null, $error);
        } catch (Throwable $e) {
            return new PushSendResult(false, null, $e->getMessage());
        }
    }
}
