<?php

namespace App\Exceptions;

use RuntimeException;

abstract class OtpException extends RuntimeException implements ApiException
{
    abstract public function errorCode(): string;

    public function status(): int
    {
        return 422;
    }

    public function context(): array
    {
        return [];
    }
}
