<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;

class OtpExpiredException extends OtpException
{
    public function __construct()
    {
        parent::__construct('OTP has expired.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::OTP_EXPIRED;
    }
}
