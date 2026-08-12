<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class InvalidQuoteTokenException extends RuntimeException implements ApiException
{
    public function __construct(string $reason = 'Quote token is invalid or has expired.')
    {
        parent::__construct($reason);
    }

    public function errorCode(): string
    {
        return ErrorCodes::QUOTE_TOKEN_INVALID;
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
