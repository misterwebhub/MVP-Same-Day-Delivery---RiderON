<?php

namespace App\Http\Resources;

use App\Models\OtpVerification;
use App\Services\Otp\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            // Only present once a partner has accepted (partner_id set) —
            // null before that, per "no auto-assign, show Unassigned until
            // someone accepts" behavior. No live GPS: partners travel fixed
            // scheduled routes between stations, so "location" here means
            // the route/stations above, not a live coordinate.
            'partner' => $this->whenLoaded('partner', fn () => $this->partner === null ? null : [
                'name' => $this->partner->user?->name,
                'phone' => $this->partner->user?->phone,
                'vehicle_type' => $this->partner->vehicle_type,
                'rating_avg' => $this->partner->rating_avg !== null ? (float) $this->partner->rating_avg : null,
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
            // Only set when the origin station's city opted into manual
            // pickup (currently Kanpur) and the customer actually entered
            // one — null everywhere else, meaning "use the origin station's
            // own address/coordinates" (still shown via `route` above).
            'pickup_address' => ($this->pickup_address_text !== null || $this->pickup_latitude !== null) ? [
                'text' => $this->pickup_address_text,
                'latitude' => $this->pickup_latitude !== null ? (float) $this->pickup_latitude : null,
                'longitude' => $this->pickup_longitude !== null ? (float) $this->pickup_longitude : null,
            ] : null,
            // Mirrors pickup_address above but for the delivery/destination
            // end (currently only opts in for Kanpur-as-destination).
            'delivery_address' => ($this->delivery_address_text !== null || $this->delivery_latitude !== null) ? [
                'text' => $this->delivery_address_text,
                'latitude' => $this->delivery_latitude !== null ? (float) $this->delivery_latitude : null,
                'longitude' => $this->delivery_longitude !== null ? (float) $this->delivery_longitude : null,
            ] : null,
            'parcel' => $this->whenLoaded('parcel', fn () => $this->parcel === null ? null : [
                'parcel_type' => $this->parcel->parcel_type,
                'weight_slab' => $this->parcel->weight_slab,
                'quantity' => $this->parcel->quantity,
                'declared_value_paise' => $this->parcel->declared_value_paise,
                'special_instructions' => $this->parcel->special_instructions,
                'photos' => $this->parcel->relationLoaded('images')
                    ? $this->parcel->images->map(fn ($image) => url(Storage::disk('public')->url($image->storage_path)))->values()
                    : [],
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
            // Shown to the booking customer only (this resource is always
            // scoped to `order.customer_id === auth()->id()` by the caller)
            // so the sender can read the pickup code out to the rider at
            // pickup, and can relay the delivery code to the receiver
            // themselves as a backup to SMS.
            'pickup_otp' => $this->otpField(OtpVerification::PURPOSE_PICKUP),
            'delivery_otp' => $this->otpField(OtpVerification::PURPOSE_DELIVERY),
            // Rider-captured proof-of-custody shots — visible to the customer too, for
            // transparency ("here's proof your parcel was actually picked up/delivered").
            'pickup_proof_photo_url' => $this->pickup_proof_photo_path
                ? url(Storage::disk('public')->url($this->pickup_proof_photo_path))
                : null,
            'delivery_proof_photo_url' => $this->delivery_proof_photo_path
                ? url(Storage::disk('public')->url($this->delivery_proof_photo_path))
                : null,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{status: string, code: string|null, expires_at: string|null, resend_count: int}|null
     */
    private function otpField(string $purpose): ?array
    {
        if (! $this->relationLoaded('otpVerifications')) {
            return null;
        }

        /** @var OtpVerification|null $otp */
        $otp = $this->otpVerifications->sortByDesc('id')->firstWhere('purpose', $purpose);

        if ($otp === null) {
            return null;
        }

        $status = match (true) {
            $otp->verified_at !== null => 'verified',
            $otp->expires_at->isPast() => 'expired',
            default => 'pending',
        };

        return [
            'status' => $status,
            // Null whenever there's nothing safe/valid to show — already
            // verified, expired, or the cache entry is simply gone (e.g.
            // cache store was cleared). The app must treat null as "not
            // available right now", not as an error, and fall back to
            // SMS / the resend button.
            'code' => $status === 'pending' ? app(OtpService::class)->peekCachedPlainOtp($otp) : null,
            'expires_at' => $otp->expires_at?->toIso8601String(),
            'resend_count' => $otp->resend_count,
        ];
    }
}
