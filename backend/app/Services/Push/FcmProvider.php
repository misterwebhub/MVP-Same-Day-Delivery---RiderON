<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Real Firebase Cloud Messaging integration using the HTTP v1 API.
 * Exchanges the service account JSON (FCM_CREDENTIALS_PATH) for a
 * short-lived OAuth2 access token via a self-signed JWT bearer grant —
 * no google/firebase SDK dependency needed. Only exercised when
 * PUSH_DRIVER=fcm and valid credentials are configured.
 */
class FcmProvider implements PushProvider
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $credentialsPath,
    ) {}

    public function send(string $fcmToken, string $title, string $body, array $data = []): PushSendResult
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->asJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token' => $fcmToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data),
                    ],
                ]);

            if ($response->successful()) {
                return new PushSendResult(true, $response->json('name'), null);
            }

            return new PushSendResult(false, null, $response->body());
        } catch (Throwable $e) {
            return new PushSendResult(false, null, $e->getMessage());
        }
    }

    private function getAccessToken(): string
    {
        $serviceAccount = json_decode(File::get($this->credentialsPath), true, flags: JSON_THROW_ON_ERROR);

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $signatureInput = "{$header}.{$claims}";
        openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $signatureInput.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ])->throw();

        return $response->json('access_token');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
