<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'order_id' => $this->order_id,
            'category' => $this->category,
            'description' => $this->description,
            'status' => $this->status,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'messages' => $this->whenLoaded('messages', fn () => $this->messages->map(fn ($message) => [
                'sender_type' => $message->sender_type,
                'message' => $message->message,
                'created_at' => $message->created_at?->toIso8601String(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
