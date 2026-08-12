<?php

namespace App\Services\Support;

use App\Models\SupportTicket;
use Illuminate\Support\Str;

/**
 * Generates support ticket numbers, mirroring BookingReferenceGenerator's
 * approach for order booking references.
 */
class TicketNumberGenerator
{
    public function generate(): string
    {
        do {
            $ticketNumber = 'TKT-'.strtoupper(Str::random(8));
        } while (SupportTicket::query()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
