<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use Carbon\CarbonInterface;

class OtpResendCooldownException extends OtpException
{
    public function __construct(public readonly CarbonInterface $retryAfter)
    {
        parent::__construct("OTP resend is on cooldown until {$retryAfter->toIso8601String()}.");
    }

    public function errorCode(): string
    {
        return ErrorCodes::OTP_RESEND_COOLDOWN;
    }

    public function status(): int
    {
        return 429;
    }

    public function context(): array
    {
        return ['retry_after' => $this->retryAfter->toIso8601String()];
    }
}
