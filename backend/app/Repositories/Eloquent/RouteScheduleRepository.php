<?php

namespace App\Repositories\Eloquent;

use App\Models\RouteSchedule;
use App\Repositories\Contracts\RouteScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RouteScheduleRepository extends BaseRepository implements RouteScheduleRepositoryInterface
{
    public function __construct(RouteSchedule $model)
    {
        parent::__construct($model);
    }

    public function forRoute(int $routeId): Collection
    {
        return $this->model->newQuery()
            ->where('route_id', $routeId)
            ->orderBy('departure_time')
            ->get();
    }
}
