<?php

namespace App\Repositories\Eloquent;

use App\Models\Route;
use App\Repositories\Contracts\RouteRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class RouteRepository extends SoftDeletableRepository implements RouteRepositoryInterface
{
    public function __construct(Route $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with(['originStation.city', 'destinationStation.city']);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->applyTrashedFilter($query, $filters);

        if (! empty($filters['origin_station_id'])) {
            $query->where('origin_station_id', $filters['origin_station_id']);
        }

        if (! empty($filters['destination_station_id'])) {
            $query->where('destination_station_id', $filters['destination_station_id']);
        }

        return $query;
    }
}
