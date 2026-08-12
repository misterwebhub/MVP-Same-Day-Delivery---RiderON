<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class InvalidCredentialsException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('Invalid phone or password.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::INVALID_CREDENTIALS;
    }

    public function status(): int
    {
        return 401;
    }

    public function context(): array
    {
        return [];
    }
}
