<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RouteScheduleRequest;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Repositories\Contracts\RouteScheduleRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RouteScheduleController extends Controller
{
    public function __construct(private readonly RouteScheduleRepositoryInterface $schedules) {}

    public function create(Route $route): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.routes.schedules.create', ['route' => $route]);
    }

    public function store(RouteScheduleRequest $request, Route $route): RedirectResponse
    {
        $this->schedules->create([...$request->validated(), 'route_id' => $route->id]);

        return redirect()->route('admin.routes.edit', $route)->with('status', 'Schedule added.');
    }

    public function edit(Route $route, RouteSchedule $schedule): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);
        abort_unless($schedule->route_id === $route->id, 404);

        return view('admin.routes.schedules.edit', ['route' => $route, 'schedule' => $schedule]);
    }

    public function update(RouteScheduleRequest $request, Route $route, RouteSchedule $schedule): RedirectResponse
    {
        abort_unless($schedule->route_id === $route->id, 404);

        $this->schedules->update($schedule, $request->validated());

        return redirect()->route('admin.routes.edit', $route)->with('status', 'Schedule updated.');
    }

    public function destroy(Route $route, RouteSchedule $schedule): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);
        abort_unless($schedule->route_id === $route->id, 404);

        $this->schedules->delete($schedule);

        return redirect()->route('admin.routes.edit', $route)->with('status', 'Schedule removed.');
    }
}
