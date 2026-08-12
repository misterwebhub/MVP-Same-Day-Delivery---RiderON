<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class InvalidRefreshTokenException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('Refresh token is invalid, expired, or has been revoked.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::REFRESH_TOKEN_INVALID;
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
