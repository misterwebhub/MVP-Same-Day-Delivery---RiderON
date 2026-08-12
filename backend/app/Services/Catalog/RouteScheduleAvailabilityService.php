<?php

namespace App\Services\Catalog;

use App\Constants\OrderStatus;
use App\Exceptions\ScheduleNotAvailableException;
use App\Exceptions\SeatUnavailableException;
use App\Models\Order;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\RouteScheduleDate;
use App\Services\PricingEngine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared schedule-availability computation used both for listing schedules
 * to a browsing customer (Task #12) and for validating a specific
 * schedule+date at order-creation time (Task #13). Availability accounts
 * for day-of-week match, blackout dates, booking cutoff, and live seat
 * counts derived from non-cancelled/non-failed orders.
 */
class RouteScheduleAvailabilityService
{
    public function __construct(private readonly PricingEngine $pricingEngine)
    {
    }

    public function availableSchedulesForDate(Route $route, string $date): Collection
    {
        $priceFrom = $this->pricingEngine->priceFrom($route);

        return $this->computeAvailability($route, $date)
            ->map(fn (array $entry) => $entry + ['price_from' => $priceFrom]);
    }

    /**
     * Asserts that the given schedule is bookable for the given date on the
     * given route, throwing the same domain exceptions a caller would need
     * to translate into the API error envelope.
     */
    public function assertBookable(Route $route, RouteSchedule $schedule, string $date): void
    {
        if ($schedule->route_id !== $route->id || ! $schedule->is_active) {
            throw new ScheduleNotAvailableException();
        }

        $entry = $this->computeAvailability($route, $date, scheduleId: $schedule->id)->first();

        if ($entry === null) {
            throw new ScheduleNotAvailableException();
        }

        if ($entry['seats_available'] < 1) {
            throw new SeatUnavailableException($entry['seats_available']);
        }
    }

    private function computeAvailability(Route $route, string $date, ?int $scheduleId = null): Collection
    {
        $dayOfWeekIso = Carbon::parse($date)->dayOfWeekIso;
        $now = Carbon::now();

        $query = RouteSchedule::query()
            ->where('route_id', $route->id)
            ->where('is_active', true)
            ->whereJsonContains('days_of_week', $dayOfWeekIso);

        if ($scheduleId !== null) {
            $query->where('id', $scheduleId);
        }

        $schedules = $query->orderBy('departure_time')->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $cancelledScheduleIds = RouteScheduleDate::query()
            ->whereIn('route_schedule_id', $schedules->pluck('id'))
            ->where('date', $date)
            ->where('is_cancelled', true)
            ->pluck('route_schedule_id')
            ->all();

        $bookedCounts = Order::query()
            ->whereIn('route_schedule_id', $schedules->pluck('id'))
            ->whereDate('booking_date', $date)
            ->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::PAYMENT_FAILED])
            ->selectRaw('route_schedule_id, count(*) as bookings')
            ->groupBy('route_schedule_id')
            ->pluck('bookings', 'route_schedule_id');

        return $schedules
            ->reject(fn (RouteSchedule $schedule) => in_array($schedule->id, $cancelledScheduleIds, true))
            ->map(function (RouteSchedule $schedule) use ($date, $now, $route, $bookedCounts) {
                $departureAt = Carbon::parse("{$date} {$schedule->departure_time}");
                $cutoffAt = $departureAt->clone()->subMinutes($schedule->booking_cutoff_minutes_before);

                if ($cutoffAt->lt($now)) {
                    return null;
                }

                $seatsAvailable = max(0, $route->max_parcels_per_schedule - (int) ($bookedCounts[$schedule->id] ?? 0));

                return [
                    'route_schedule_id' => $schedule->id,
                    'departure_time' => $schedule->departure_time,
                    'arrival_time' => $schedule->arrival_time,
                    'cutoff_at' => $cutoffAt->toIso8601String(),
                    'seats_available' => $seatsAvailable,
                ];
            })
            ->filter()
            ->values();
    }
}
