<?php

namespace App\Services\Push;

interface PushProvider
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $fcmToken, string $title, string $body, array $data = []): PushSendResult;
}
