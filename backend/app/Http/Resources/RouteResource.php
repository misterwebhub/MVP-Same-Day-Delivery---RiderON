<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects `price_from` to already be set as a computed attribute on the
 * Route model (via PricingEngine::priceFrom) before being wrapped here.
 */
class RouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origin_station' => $this->whenLoaded('originStation', fn () => new StationResource($this->originStation)),
            'destination_station' => $this->whenLoaded('destinationStation', fn () => new StationResource($this->destinationStation)),
            'distance_km' => (float) $this->distance_km,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'cutoff_time' => $this->cutoff_time,
            'waiting_time_minutes' => $this->waiting_time_minutes,
            'price_from' => $this->price_from,
        ];
    }
}
