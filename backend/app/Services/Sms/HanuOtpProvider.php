<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Real integration with the HanuOtp SMS API
 * (https://api.hanuotp.in/sms-otp.php). Unlike Msg91Provider, this is an
 * OTP-only endpoint — it takes the raw numeric code as its own query
 * parameter rather than a free-text message, so sendOtp() is the "real"
 * entry point and send() (generic text) is only a best-effort fallback
 * that extracts a numeric code out of the message body. Only exercised
 * when SMS_DRIVER=hanuotp.
 */
class HanuOtpProvider implements SmsProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $templateId,
    ) {}

    public function sendOtp(string $toPhone, string $otp, string $purpose): SmsSendResult
    {
        return $this->dispatch($toPhone, $otp);
    }

    /**
     * HanuOtp has no generic "send arbitrary text" endpoint. If something
     * ever calls send() directly on this provider, pull the numeric code
     * back out of the message so it still does something useful instead of
     * silently failing.
     */
    public function send(string $toPhone, string $message): SmsSendResult
    {
        if (! preg_match('/\d{3,8}/', $message, $matches)) {
            return new SmsSendResult(false, null, 'HanuOtp only supports sending numeric OTP codes; no code found in message.');
        }

        return $this->dispatch($toPhone, $matches[0]);
    }

    private function dispatch(string $toPhone, string $otp): SmsSendResult
    {
        try {
            $response = Http::get($this->baseUrl, [
                'number' => $this->normalizePhone($toPhone),
                'OTP' => $otp,
                'apikey' => $this->apiKey,
                'templatesid' => $this->templateId,
            ]);

            if (! $response->successful()) {
                Log::warning('[HanuOtpProvider] SMS request failed', [
                    'to' => $toPhone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new SmsSendResult(false, null, "HTTP {$response->status()}: {$response->body()}");
            }

            $body = trim($response->body());

            // Response shape isn't formally documented; accept either JSON
            // with a status/success-ish field or a plain-text body that
            // mentions success, and treat everything else as a failure with
            // the raw body preserved for debugging.
            $json = json_decode($body, true);
            if (is_array($json)) {
                $status = strtolower((string) ($json['status'] ?? $json['message'] ?? ''));
                $success = str_contains($status, 'success') || ($json['success'] ?? false) === true;
                $messageId = $json['request_id'] ?? $json['id'] ?? null;

                return new SmsSendResult($success, $messageId, $success ? null : $body);
            }

            $success = $body === '' || str_contains(strtolower($body), 'success');

            return new SmsSendResult($success, $success ? null : null, $success ? null : $body);
        } catch (Throwable $e) {
            Log::error('[HanuOtpProvider] SMS request threw', [
                'to' => $toPhone,
                'error' => $e->getMessage(),
            ]);

            return new SmsSendResult(false, null, $e->getMessage());
        }
    }

    /**
     * HanuOtp expects a bare 10-digit Indian mobile number (no +91/91
     * prefix) — e.g. "8299226971", matching the sample request this
     * provider was built against.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return substr($digits, -10);
    }
}
