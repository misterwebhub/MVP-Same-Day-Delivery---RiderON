<?php

namespace App\Services\Push;

final class PushSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId,
        public readonly ?string $error,
    ) {}
}
