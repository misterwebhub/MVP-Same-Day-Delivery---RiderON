<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface RouteScheduleRepositoryInterface extends RepositoryInterface
{
    public function forRoute(int $routeId): Collection;
}
