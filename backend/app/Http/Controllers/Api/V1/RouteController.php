<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\RouteIndexRequest;
use App\Http\Requests\Catalog\RouteScheduleAvailabilityRequest;
use App\Http\Resources\RouteResource;
use App\Models\Order;
use App\Models\Route;
use App\Services\Catalog\RouteScheduleAvailabilityService;
use App\Services\PricingEngine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly RouteScheduleAvailabilityService $availabilityService,
    ) {
    }

    public function index(RouteIndexRequest $request): JsonResponse
    {
        $query = Route::query()
            ->with(['originStation', 'destinationStation'])
            ->where('is_active', true);

        if ($request->filled('origin_station_id')) {
            $route = $query
                ->where('origin_station_id', $request->integer('origin_station_id'))
                ->where('destination_station_id', $request->integer('destination_station_id'))
                ->firstOrFail();

            return $this->success(new RouteResource($this->withPriceFrom($route)));
        }

        $routes = $query->orderBy('id')->get()->each(fn (Route $route) => $this->withPriceFrom($route));

        return $this->success(RouteResource::collection($routes));
    }

    public function show(Route $route): JsonResponse
    {
        abort_unless($route->is_active, 404);

        $route->load(['originStation', 'destinationStation']);

        return $this->success(new RouteResource($this->withPriceFrom($route)));
    }

    public function schedules(Route $route, RouteScheduleAvailabilityRequest $request): JsonResponse
    {
        abort_unless($route->is_active, 404);

        $date = $request->string('date')->toString();

        return $this->success($this->availabilityService->availableSchedulesForDate($route, $date));
    }

    public function popular(): JsonResponse
    {
        $limit = (int) config('routes_catalog.popular_routes_limit');
        $windowStart = Carbon::now()->subDays((int) config('routes_catalog.popular_routes_window_days'));

        $rankedRouteIds = Order::query()
            ->where('created_at', '>=', $windowStart)
            ->whereNotIn('status', [OrderStatus::DRAFT, OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED])
            ->selectRaw('route_id, count(*) as bookings')
            ->groupBy('route_id')
            ->orderByDesc('bookings')
            ->limit($limit)
            ->pluck('route_id')
            ->all();

        $routes = Route::query()
            ->with(['originStation', 'destinationStation'])
            ->where('is_active', true)
            ->whereIn('id', $rankedRouteIds)
            ->get()
            ->sortBy(fn (Route $route) => array_search($route->id, $rankedRouteIds, true))
            ->values();

        if ($routes->count() < $limit) {
            $fallbackRoutes = Route::query()
                ->with(['originStation', 'destinationStation'])
                ->where('is_active', true)
                ->whereNotIn('id', $routes->pluck('id'))
                ->orderBy('id')
                ->limit($limit - $routes->count())
                ->get();

            $routes = $routes->concat($fallbackRoutes);
        }

        $routes->each(fn (Route $route) => $this->withPriceFrom($route));

        return $this->success(RouteResource::collection($routes));
    }

    private function withPriceFrom(Route $route): Route
    {
        $route->setAttribute('price_from', $this->pricingEngine->priceFrom($route));

        return $route;
    }
}
