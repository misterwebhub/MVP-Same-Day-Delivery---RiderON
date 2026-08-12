<?php

namespace App\Exceptions;

/**
 * Contract for exceptions that map directly onto the API's error envelope
 * (see docs/03-api-architecture.md). Implemented by domain exceptions so the
 * global handler (App\Exceptions\ApiExceptionRenderer) can render a correct
 * status code + stable error_code + any extra context without a hardcoded
 * switch statement per exception type.
 */
interface ApiException
{
    public function errorCode(): string;

    public function status(): int;

    /**
     * Extra machine-readable context merged into the error envelope, e.g.
     * {"attempts_remaining": 3} or {"locked_until": "2026-01-01T00:00:00+05:30"}.
     *
     * @return array<string, mixed>
     */
    public function context(): array;
}
