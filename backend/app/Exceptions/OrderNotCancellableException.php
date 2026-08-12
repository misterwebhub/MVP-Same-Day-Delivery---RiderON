<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class OrderNotCancellableException extends RuntimeException implements ApiException
{
    public function __construct(private readonly string $reason = 'This order can no longer be cancelled through this endpoint.')
    {
        parent::__construct($this->reason);
    }

    public function errorCode(): string
    {
        return ErrorCodes::ORDER_NOT_CANCELLABLE;
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
