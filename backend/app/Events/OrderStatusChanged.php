<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?string $fromStatus,
        public readonly string $toStatus,
        public readonly string $actorType,
        public readonly ?int $actorId,
    ) {}
}
