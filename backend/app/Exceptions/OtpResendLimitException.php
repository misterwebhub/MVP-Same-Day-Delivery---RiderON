<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;

class OtpResendLimitException extends OtpException
{
    public function __construct()
    {
        parent::__construct('OTP resend limit exceeded for this order/purpose.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::OTP_RESEND_LIMIT_EXCEEDED;
    }

    public function status(): int
    {
        return 429;
    }
}
