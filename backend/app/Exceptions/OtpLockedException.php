<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use Carbon\CarbonInterface;

class OtpLockedException extends OtpException
{
    public function __construct(public readonly CarbonInterface $lockedUntil)
    {
        parent::__construct("OTP is locked until {$lockedUntil->toIso8601String()}.");
    }

    public function errorCode(): string
    {
        return ErrorCodes::OTP_LOCKED;
    }

    public function status(): int
    {
        return 423;
    }

    public function context(): array
    {
        return ['locked_until' => $this->lockedUntil->toIso8601String()];
    }
}
