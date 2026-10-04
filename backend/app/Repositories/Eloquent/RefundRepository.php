<?php

namespace App\Repositories\Eloquent;

use App\Models\Refund;
use App\Repositories\Contracts\RefundRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class RefundRepository extends BaseRepository implements RefundRepositoryInterface
{
    public function __construct(Refund $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with(['order', 'payment', 'approvedBy']);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        return $query;
    }
}
