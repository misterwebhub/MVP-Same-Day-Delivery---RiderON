<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'status' => $this->status,
            'booking_date' => $this->booking_date?->toDateString(),
            'route' => $this->whenLoaded('route', fn () => [
                'id' => $this->route->id,
                'origin_station' => $this->route->relationLoaded('originStation')
                    ? new StationResource($this->route->originStation)
                    : null,
                'destination_station' => $this->route->relationLoaded('destinationStation')
                    ? new StationResource($this->route->destinationStation)
                    : null,
                'distance_km' => (float) $this->route->distance_km,
                'estimated_duration_minutes' => $this->route->estimated_duration_minutes,
            ]),
            'route_schedule' => $this->whenLoaded('routeSchedule', fn () => [
                'id' => $this->routeSchedule->id,
                'departure_time' => $this->routeSchedule->departure_time,
                'arrival_time' => $this->routeSchedule->arrival_time,
            ]),
            'sender' => [
                'name' => $this->sender_name,
                'phone' => $this->sender_phone,
                'landmark' => $this->sender_landmark,
            ],
            'receiver' => [
                'name' => $this->receiver_name,
                'phone' => $this->receiver_phone,
                'landmark' => $this->receiver_landmark,
            ],
            'parcel' => $this->whenLoaded('parcel', fn () => $this->parcel === null ? null : [
                'parcel_type' => $this->parcel->parcel_type,
                'weight_slab' => $this->parcel->weight_slab,
                'quantity' => $this->parcel->quantity,
                'declared_value_paise' => $this->parcel->declared_value_paise,
                'special_instructions' => $this->parcel->special_instructions,
            ]),
            'price_breakdown' => $this->price_breakdown,
            'total_amount_paise' => $this->total_amount_paise,
            'currency' => $this->currency,
            'payment' => $this->whenLoaded('payments', function () {
                $payment = $this->payments->last();

                return $payment === null ? null : [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'provider_order_id' => $payment->provider_order_id,
                    'amount_paise' => $payment->amount_paise,
                    'status' => $payment->status,
                ];
            }),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
