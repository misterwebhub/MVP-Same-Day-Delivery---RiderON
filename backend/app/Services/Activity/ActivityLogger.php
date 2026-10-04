<?php

namespace App\Services\Activity;

use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\Station;
use Illuminate\Http\Request;

/**
 * Single choke point for writing to order_activity_logs (docs fraud-
 * prevention addendum). IP address is always captured server-side from the
 * request — free, no client cooperation needed. GPS coordinates are only
 * present when the caller passes them (the mobile app sends them on a
 * best-effort basis; a denied location permission must never block the
 * underlying action, so lat/lng are nullable everywhere).
 *
 * When $compareToStation is given (arrival-type events only), this also
 * computes the straight-line distance between the reported GPS and the
 * station's known coordinates — the cheapest fraud signal in this whole
 * table: a rider "arriving" from 40km away is instantly visible to admin
 * without them having to interpret raw coordinates themselves.
 */
class ActivityLogger
{
    public function log(
        Order $order,
        string $event,
        string $actorType,
        ?int $actorId,
        ?Request $request = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?Station $compareToStation = null,
        array $metadata = [],
    ): OrderActivityLog {
        $distanceMeters = null;

        if ($latitude !== null && $longitude !== null && $compareToStation !== null
            && $compareToStation->latitude !== null && $compareToStation->longitude !== null) {
            $distanceMeters = (int) round($this->haversineMeters(
                $latitude,
                $longitude,
                (float) $compareToStation->latitude,
                (float) $compareToStation->longitude,
            ));
        }

        return OrderActivityLog::create([
            'order_id' => $order->id,
            'event' => $event,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request !== null ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            'latitude' => $latitude !== null ? (string) $latitude : null,
            'longitude' => $longitude !== null ? (string) $longitude : null,
            'distance_from_target_meters' => $distanceMeters,
            'metadata' => $metadata !== [] ? $metadata : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Great-circle distance between two lat/lng points, in metres.
     */
    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
