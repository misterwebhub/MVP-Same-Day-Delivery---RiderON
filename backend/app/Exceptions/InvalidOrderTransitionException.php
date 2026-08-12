<?php

namespace App\Exceptions;

use App\Constants\ErrorCodes;
use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException implements ApiException
{
    public function __construct(
        public readonly ?string $fromStatus,
        public readonly string $toStatus,
    ) {
        parent::__construct(
            sprintf('Cannot transition order from [%s] to [%s].', $fromStatus ?? 'null', $toStatus)
        );
    }

    public function errorCode(): string
    {
        return ErrorCodes::INVALID_STATE_TRANSITION;
    }

    public function status(): int
    {
        return 409;
    }

    public function context(): array
    {
        return ['from_status' => $this->fromStatus, 'to_status' => $this->toStatus];
    }
}
