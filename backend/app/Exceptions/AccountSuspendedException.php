<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class AccountSuspendedException extends RuntimeException implements ApiException
{
    public function __construct()
    {
        parent::__construct('This account has been suspended.');
    }

    public function errorCode(): string
    {
        return ErrorCodes::ACCOUNT_SUSPENDED;
    }

    public function status(): int
    {
        return 403;
    }

    public function context(): array
    {
        return [];
    }
}
