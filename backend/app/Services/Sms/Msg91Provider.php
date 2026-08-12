<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Real MSG91 integration (flow/template API). Indian DLT regulations
 * require transactional SMS to go through a pre-approved sender id +
 * template, so $message here is passed as the template's variable —
 * the template registered under MSG91_TEMPLATE_ID must match the copy
 * used by OtpService. Only exercised when SMS_DRIVER=msg91.
 */
class Msg91Provider implements SmsProvider
{
    private const BASE_URL = 'https://control.msg91.com/api/v5/flow/';

    public function __construct(
        private readonly string $authKey,
        private readonly string $senderId,
        private readonly ?string $templateId,
    ) {}

    public function send(string $toPhone, string $message): SmsSendResult
    {
        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post(self::BASE_URL, [
                'template_id' => $this->templateId,
                'sender' => $this->senderId,
                'short_url' => '0',
                'recipients' => [[
                    'mobiles' => $this->normalizePhone($toPhone),
                    'VAR1' => $message,
                ]],
            ]);

            if ($response->successful() && $response->json('type') === 'success') {
                return new SmsSendResult(true, $response->json('request_id'), null);
            }

            return new SmsSendResult(false, null, $response->body());
        } catch (Throwable $e) {
            return new SmsSendResult(false, null, $e->getMessage());
        }
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return str_starts_with($digits, '91') ? $digits : '91'.ltrim($digits, '0');
    }
}
