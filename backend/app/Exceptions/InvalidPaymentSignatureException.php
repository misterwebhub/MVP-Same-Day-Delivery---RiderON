<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class InvalidPaymentSignatureException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('The payment signature could not be verified.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::PAYMENT_SIGNATURE_INVALID;
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
