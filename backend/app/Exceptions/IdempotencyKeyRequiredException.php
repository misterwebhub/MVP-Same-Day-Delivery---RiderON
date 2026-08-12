<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class IdempotencyKeyRequiredException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('An Idempotency-Key header is required for this request.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::IDEMPOTENCY_KEY_REQUIRED;
    }

    public function status(): int
    {
        return 400;
    }

    public function context(): array
    {
        return [];
    }
}
