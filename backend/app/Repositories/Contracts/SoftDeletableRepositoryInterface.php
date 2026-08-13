<?php

namespace App\Repositories\Contracts;

/**
 * Adds trashed-row handling for repositories backed by SoftDeletes models
 * (mirrors the old Filament resources' TrashedFilter + restore/force-delete
 * bulk actions).
 */
interface SoftDeletableRepositoryInterface extends RepositoryInterface
{
    public function findWithTrashed(int $id): ?\Illuminate\Database\Eloquent\Model;

    public function restore(int $id): bool;

    public function forceDelete(int $id): bool;
}
