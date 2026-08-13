<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\SoftDeletableRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class SoftDeletableRepository extends BaseRepository implements SoftDeletableRepositoryInterface
{
    public function findWithTrashed(int $id): ?Model
    {
        return $this->model->newQuery()->withTrashed()->find($id);
    }

    public function restore(int $id): bool
    {
        $model = $this->findWithTrashed($id);

        if ($model === null) {
            return false;
        }

        return (bool) $model->restore();
    }

    public function forceDelete(int $id): bool
    {
        $model = $this->findWithTrashed($id);

        if ($model === null) {
            return false;
        }

        return (bool) $model->forceDelete();
    }

    /**
     * Applies the trashed-state filter shared by every soft-delete-aware
     * admin list (mirrors the old Filament TrashedFilter: only / with / without).
     */
    protected function applyTrashedFilter(Builder $query, array $filters): Builder
    {
        return match ($filters['trashed'] ?? null) {
            'only' => $query->onlyTrashed(),
            'with' => $query->withTrashed(),
            default => $query,
        };
    }
}
