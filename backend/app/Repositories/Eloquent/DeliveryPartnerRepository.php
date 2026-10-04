<?php

namespace App\Repositories\Eloquent;

use App\Models\DeliveryPartner;
use App\Repositories\Contracts\DeliveryPartnerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class DeliveryPartnerRepository extends SoftDeletableRepository implements DeliveryPartnerRepositoryInterface
{
    public function __construct(DeliveryPartner $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with(['user', 'currentHomeCity']);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->applyTrashedFilter($query, $filters);

        if (! empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        if (! empty($filters['current_home_city_id'])) {
            $query->where('current_home_city_id', $filters['current_home_city_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('partner_code', 'like', "%{$filters['search']}%");
        }

        return $query;
    }
}
