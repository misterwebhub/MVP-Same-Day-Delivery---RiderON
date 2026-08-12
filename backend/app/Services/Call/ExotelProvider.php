<?php

namespace App\Services\Call;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Real Exotel "connect two numbers" integration for call masking.
 * Only exercised when CALL_DRIVER=exotel and EXOTEL_* credentials are configured.
 */
class ExotelProvider implements CallProvider
{
    public function __construct(
        private readonly string $sid,
        private readonly string $token,
        private readonly string $callerId,
    ) {}

    public function initiateMaskedCall(string $fromPhone, string $toPhone): CallInitiationResult
    {
        try {
            $response = Http::withBasicAuth($this->sid, $this->token)
                ->asForm()
                ->post("https://api.exotel.com/v1/Accounts/{$this->sid}/Calls/connect.json", [
                    'From' => $fromPhone,
                    'To' => $toPhone,
                    'CallerId' => $this->callerId,
                ]);

            if ($response->successful()) {
                return new CallInitiationResult(true, $response->json('Call.Sid'), null);
            }

            return new CallInitiationResult(false, null, $response->body());
        } catch (Throwable $e) {
            return new CallInitiationResult(false, null, $e->getMessage());
        }
    }
}
