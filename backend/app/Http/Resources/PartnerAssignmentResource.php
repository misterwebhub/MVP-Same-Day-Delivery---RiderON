<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Partner-facing order shape (docs/06). Deliberately omits everything the
 * partner app must never see: declared_value_paise (only a derived
 * "handle with care" boolean), unmasked phone numbers, payment/pricing
 * breakdown, other partners' identities, customer email, and any OTP value.
 */
class PartnerAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'status' => $this->status,
            'booking_date' => $this->booking_date?->toDateString(),
            'route' => $this->whenLoaded('route', fn () => [
                'origin_station' => $this->route->relationLoaded('originStation')
                    ? new StationResource($this->route->originStation)
                    : null,
                'destination_station' => $this->route->relationLoaded('destinationStation')
                    ? new StationResource($this->route->destinationStation)
                    : null,
            ]),
            'route_schedule' => $this->whenLoaded('routeSchedule', fn () => [
                'departure_time' => $this->routeSchedule->departure_time,
                'arrival_time' => $this->routeSchedule->arrival_time,
            ]),
            'sender' => [
                'name' => $this->sender_name,
                'phone' => $this->maskPhone($this->sender_phone),
                'landmark' => $this->sender_landmark,
            ],
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $this->maskPhone($this->receiver_phone),
                'landmark' => $this->receiver_landmark,
            ],
            'parcel' => $this->whenLoaded('parcel', fn () => $this->parcel === null ? null : [
                'parcel_type' => $this->parcel->parcel_type,
                'weight_slab' => $this->parcel->weight_slab,
                'quantity' => $this->parcel->quantity,
                'high_value' => $this->parcel->declared_value_paise >= (int) config('parcel.high_value_threshold_paise'),
                'special_instructions' => $this->parcel->special_instructions,
            ]),
            'waiting_deadline_at' => $this->waiting_deadline_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $lastFour = substr($phone, -4);

        return str_repeat('•', max(strlen($phone) - 4, 0)).$lastFour;
    }
}
