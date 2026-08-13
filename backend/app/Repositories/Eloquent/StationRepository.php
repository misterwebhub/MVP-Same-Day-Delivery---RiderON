<?php

namespace App\Repositories\Eloquent;

use App\Models\Station;
use App\Repositories\Contracts\StationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class StationRepository extends SoftDeletableRepository implements StationRepositoryInterface
{
    public function __construct(Station $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with('city');
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->applyTrashedFilter($query, $filters);

        if (! empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query;
    }
}
