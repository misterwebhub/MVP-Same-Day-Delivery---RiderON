<?php

namespace App\Services\Call;

final class CallInitiationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerCallSid,
        public readonly ?string $error,
    ) {}
}
