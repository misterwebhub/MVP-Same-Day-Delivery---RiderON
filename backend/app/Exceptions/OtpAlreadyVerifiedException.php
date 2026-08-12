<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;

class OtpAlreadyVerifiedException extends OtpException
{
    public function __construct()
    {
        parent::__construct('OTP has already been verified.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::OTP_ALREADY_VERIFIED;
    }

    public function status(): int
    {
        return 409;
    }
}
