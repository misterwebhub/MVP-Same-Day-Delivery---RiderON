<?php

namespace App\Services\Call;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockCallProvider implements CallProvider
{
    public function initiateMaskedCall(string $fromPhone, string $toPhone): CallInitiationResult
    {
        Log::info('[MockCallProvider] Masked call not actually initiated (CALL_DRIVER=mock)', [
            'from' => $fromPhone,
            'to' => $toPhone,
        ]);

        return new CallInitiationResult(true, 'mock_call_'.Str::random(12), null);
    }
}
