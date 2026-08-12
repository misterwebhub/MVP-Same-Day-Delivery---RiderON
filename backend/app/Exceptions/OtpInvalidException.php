<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;

class OtpInvalidException extends OtpException
{
    public function __construct(public readonly int $attemptsRemaining)
    {
        parent::__construct("OTP is invalid. {$attemptsRemaining} attempt(s) remaining.");
    }

    public function errorCode(): string
    {
        return ErrorCodes::OTP_INVALID;
    }

    public function context(): array
    {
        return ['attempts_remaining' => $this->attemptsRemaining];
    }
}
