<?php

namespace App\Services\Call;

/**
 * Connects two real phone numbers via a masked/virtual number so neither
 * party's actual number is exposed to the other (customer <-> partner).
 */
interface CallProvider
{
    public function initiateMaskedCall(string $fromPhone, string $toPhone): CallInitiationResult;
}
