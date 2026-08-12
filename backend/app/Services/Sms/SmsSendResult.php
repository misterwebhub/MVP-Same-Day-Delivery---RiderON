<?php

namespace App\Services\Sms;

final class SmsSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId,
        public readonly ?string $error,
    ) {}
}
