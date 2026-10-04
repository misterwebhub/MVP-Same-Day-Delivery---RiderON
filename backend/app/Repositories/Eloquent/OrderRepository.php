<?php

namespace App\Repositories\Eloquent;

use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderRepository extends SoftDeletableRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with(['customer', 'route.originStation', 'route.destinationStation', 'partner.user']);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->applyTrashedFilter($query, $filters);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Powers the "Recheck Orders" admin tab — only orders a partner has
        // actually been matched to (partner_id set), regardless of status,
        // so ops can re-verify a rider's assigned workload for a given day.
        if (! empty($filters['assigned_only'])) {
            $query->whereNotNull('partner_id');
        }

        if (! empty($filters['booking_date'])) {
            $query->whereDate('booking_date', $filters['booking_date']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('booking_reference', 'like', "%{$filters['search']}%")
                    ->orWhere('sender_phone', 'like', "%{$filters['search']}%")
                    ->orWhere('receiver_phone', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }

    /**
     * Mirrors PartnerAssignmentService::attemptAssignment()'s eligibility
     * query, but returns every match (not just the best one) so an admin
     * can pick manually.
     *
     * No same-day "busy" exclusion: a partner travels a scheduled
     * train/bus route and can carry multiple parcels on one trip, so
     * already having another live order on the same booking_date does not
     * make them ineligible for another same-day order.
     *
     * Eligibility matches the partner's home city against EITHER end of
     * the order's route (origin or destination), not just the origin: a
     * partner rides the corridor round-trip — e.g. a Kanpur-based partner
     * carries outbound parcels to Lucknow, then also picks up parcels in
     * Lucknow for the return leg back to Kanpur — so they must stay
     * eligible for both directions of their home corridor.
     */
    public function eligiblePartners(Order $order): Collection
    {
        $order->loadMissing('route.originStation', 'route.destinationStation');
        $originCityId = $order->route->originStation->city_id;
        $destinationCityId = $order->route->destinationStation->city_id;

        return DeliveryPartner::query()
            ->where('is_active', true)
            ->where('verification_status', DeliveryPartner::VERIFICATION_VERIFIED)
            ->whereIn('current_home_city_id', array_unique([$originCityId, $destinationCityId]))
            ->with('user')
            ->orderBy('completed_deliveries_count')
            ->get();
    }
}
