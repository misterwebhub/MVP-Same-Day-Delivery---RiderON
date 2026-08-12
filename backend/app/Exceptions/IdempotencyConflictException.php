<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class IdempotencyConflictException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('A request with this Idempotency-Key is already being processed.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::IDEMPOTENCY_CONFLICT;
    }

    public function status(): int
    {
        return 409;
    }

    public function context(): array
    {
        return [];
    }
}
