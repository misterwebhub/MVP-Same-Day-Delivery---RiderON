<?php

namespace App\Repositories\Eloquent;

use App\Models\PricingRule;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PricingRuleRepository extends BaseRepository implements PricingRuleRepositoryInterface
{
    public function __construct(PricingRule $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with('route.originStation', 'route.destinationStation');
    }

    public function forRoute(int $routeId): Collection
    {
        return $this->model->newQuery()->where('route_id', $routeId)->get();
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }

        if (! empty($filters['rule_type'])) {
            $query->where('rule_type', $filters['rule_type']);
        }

        return $query;
    }
}
