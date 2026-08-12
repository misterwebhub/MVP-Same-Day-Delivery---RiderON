<?php

namespace App\Services\Sms;

interface SmsProvider
{
    public function send(string $toPhone, string $message): SmsSendResult;
}
