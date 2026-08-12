<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class ScheduleNotAvailableException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('The selected schedule is not available for booking.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::SCHEDULE_NOT_AVAILABLE;
    }

    public function status(): int
    {
        return 422;
    }

    public function context(): array
    {
        return [];
    }
}
