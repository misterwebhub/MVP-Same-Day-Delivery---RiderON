<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side proxy for Google Places Autocomplete/Details, used by the
 * customer app's AddressAutocompleteField (manual pickup/delivery address
 * inputs, Kanpur leg only — see config('parcel.manual_address_cities')).
 *
 * Google's Places Autocomplete/Details JSON endpoints don't send CORS
 * headers, so a direct browser fetch from Expo web is blocked outright
 * (works fine on native). Proxying here fixes that for every platform in
 * one place, and keeps the Google API key out of the client bundle instead
 * of shipping it in EXPO_PUBLIC_* env vars.
 *
 * Best-effort by design: any failure (missing key, quota, network, bad
 * Google response) returns an empty-but-successful result rather than an
 * error, so the app's existing "fall back to plain text" behavior in
 * AddressAutocompleteField just sees zero predictions and degrades quietly.
 */
class PlacesController extends Controller
{
    public function autocomplete(Request $request): JsonResponse
    {
        $input = trim((string) $request->query('input', ''));
        if ($input === '') {
            return $this->success(['predictions' => []]);
        }

        $key = config('services.google_places.key');
        if (! $key) {
            return $this->success(['predictions' => []]);
        }

        try {
            $params = [
                'input' => $input,
                'components' => 'country:in',
                // 'geocode' biases results toward real addresses (streets,
                // localities, pincodes) instead of arbitrary points of
                // interest like "Kanpur Central" railway station — this is
                // what made typed addresses look like landmark names instead
                // of deliverable addresses.
                'types' => 'geocode',
                'key' => $key,
            ];

            // Bias toward wherever the customer currently is (if the app
            // sent it), Zomato/Porter-style, so nearby streets rank first
            // instead of same-named places across the country.
            [$lat, $lng] = $this->originFromRequest($request);
            if ($lat !== null && $lng !== null) {
                $params['location'] = "{$lat},{$lng}";
                $params['radius'] = 50000;
            }

            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);

            $body = $response->json();
            if (! $response->ok() || ! is_array($body) || ! in_array($body['status'] ?? null, ['OK', 'ZERO_RESULTS'], true)) {
                Log::warning('Places autocomplete proxy: non-OK response', ['status' => $body['status'] ?? $response->status()]);

                return $this->success(['predictions' => []]);
            }

            $predictions = collect($body['predictions'] ?? [])
                ->map(fn (array $p) => [
                    'place_id' => $p['place_id'] ?? null,
                    'description' => $p['description'] ?? null,
                ])
                ->filter(fn (array $p) => $p['place_id'] !== null && $p['description'] !== null)
                ->values();

            return $this->success(['predictions' => $predictions]);
        } catch (\Throwable $e) {
            Log::warning('Places autocomplete proxy failed', ['error' => $e->getMessage()]);

            return $this->success(['predictions' => []]);
        }
    }

    public function details(Request $request): JsonResponse
    {
        $placeId = trim((string) $request->query('place_id', ''));
        if ($placeId === '') {
            return $this->success(['location' => null, 'formatted_address' => null, 'postal_code' => null]);
        }

        $key = config('services.google_places.key');
        if (! $key) {
            return $this->success(['location' => null, 'formatted_address' => null, 'postal_code' => null]);
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'geometry,formatted_address,address_component',
                'key' => $key,
            ]);

            $body = $response->json();
            $result = $body['result'] ?? null;
            $location = $result['geometry']['location'] ?? null;
            if (! $response->ok() || ! is_array($body) || ($body['status'] ?? null) !== 'OK' || ! is_array($location)) {
                Log::warning('Places details proxy: non-OK response', ['status' => $body['status'] ?? $response->status()]);

                return $this->success(['location' => null, 'formatted_address' => null, 'postal_code' => null]);
            }

            return $this->success([
                'location' => [
                    'lat' => (float) $location['lat'],
                    'lng' => (float) $location['lng'],
                ],
                'formatted_address' => $result['formatted_address'] ?? null,
                'postal_code' => $this->extractPostalCode($result['address_components'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Places details proxy failed', ['error' => $e->getMessage()]);

            return $this->success(['location' => null, 'formatted_address' => null, 'postal_code' => null]);
        }
    }

    /**
     * Reverse-geocodes a GPS fix into a human address + pincode — the
     * Zomato/Porter "we found you at ___, tap to edit" step. Best-effort
     * like the rest of this controller: any failure just returns nulls so
     * the caller falls back to an empty, manually-typed field.
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return $this->success(['formatted_address' => null, 'postal_code' => null]);
        }

        $key = config('services.google_places.key');
        if (! $key) {
            return $this->success(['formatted_address' => null, 'postal_code' => null]);
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $key,
            ]);

            $body = $response->json();
            $result = $body['results'][0] ?? null;
            if (! $response->ok() || ! is_array($body) || ($body['status'] ?? null) !== 'OK' || ! is_array($result)) {
                Log::warning('Places reverse-geocode proxy: non-OK response', ['status' => $body['status'] ?? $response->status()]);

                return $this->success(['formatted_address' => null, 'postal_code' => null]);
            }

            return $this->success([
                'formatted_address' => $result['formatted_address'] ?? null,
                'postal_code' => $this->extractPostalCode($result['address_components'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Places reverse-geocode proxy failed', ['error' => $e->getMessage()]);

            return $this->success(['formatted_address' => null, 'postal_code' => null]);
        }
    }

    /** @return array{0: float|null, 1: float|null} */
    private function originFromRequest(Request $request): array
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        if (is_numeric($lat) && is_numeric($lng)) {
            return [(float) $lat, (float) $lng];
        }

        return [null, null];
    }

    private function extractPostalCode(array $components): ?string
    {
        foreach ($components as $component) {
            if (in_array('postal_code', $component['types'] ?? [], true)) {
                return $component['long_name'] ?? null;
            }
        }

        return null;
    }
}
