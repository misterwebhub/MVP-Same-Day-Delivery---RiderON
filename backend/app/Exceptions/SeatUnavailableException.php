<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class SeatUnavailableException extends RuntimeException implements ApiException
{
    public function __construct(private readonly int $seatsAvailable)
    {
        parent::__construct('No seats are available on the selected schedule.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::SEAT_UNAVAILABLE;
    }

    public function status(): int
    {
        return 422;
    }

    public function context(): array
    {
        return [
            'seats_available' => $this->seatsAvailable,
        ];
    }
}
