<?php

namespace App\Repositories\Eloquent;

use App\Models\OtpVerificationLog;
use App\Repositories\Contracts\OtpVerificationLogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class OtpVerificationLogRepository extends BaseRepository implements OtpVerificationLogRepositoryInterface
{
    public function __construct(OtpVerificationLog $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->newQuery()->with('otpVerification');
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['result'])) {
            $query->where('result', $filters['result']);
        }

        if (! empty($filters['attempted_by_type'])) {
            $query->where('attempted_by_type', $filters['attempted_by_type']);
        }

        return $query;
    }
}
