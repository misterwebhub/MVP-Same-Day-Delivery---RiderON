<?php

namespace App\Services;

use Carbon\CarbonInterface;

readonly class PricingQuote
{
    /**
     * @param  array<int, array{label: string, amount_paise: int}>  $breakdown
     */
    public function __construct(
        public array $breakdown,
        public int $totalAmountPaise,
        public string $quoteToken,
        public CarbonInterface $quoteExpiresAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'breakdown' => $this->breakdown,
            'total_amount_paise' => $this->totalAmountPaise,
            'quote_token' => $this->quoteToken,
            'quote_expires_at' => $this->quoteExpiresAt->toIso8601String(),
        ];
    }
}
