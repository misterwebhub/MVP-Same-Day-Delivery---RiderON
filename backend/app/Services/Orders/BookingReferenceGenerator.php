<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Generates booking references in the RID-XX-XXXXXX format shown in
 * docs/03-api-architecture.md (e.g. RID-KL-8F3K2Q). The middle/trailing
 * segments are random, not derived from station codes.
 */
class BookingReferenceGenerator
{
    public function generate(): string
    {
        do {
            $reference = 'RID-'.strtoupper(Str::random(2)).'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('booking_reference', $reference)->exists());

        return $reference;
    }
}
