<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RouteRequest;
use App\Models\Route;
use App\Repositories\Contracts\RouteRepositoryInterface;
use App\Repositories\Contracts\RouteScheduleRepositoryInterface;
use App\Repositories\Contracts\StationRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RouteController extends Controller
{
    public function __construct(
        private readonly RouteRepositoryInterface $routes,
        private readonly StationRepositoryInterface $stations,
        private readonly RouteScheduleRepositoryInterface $schedules,
    ) {}

    public function index(Request $request): View
    {
        $routes = $this->routes->paginate(15, $request->only(['search', 'trashed', 'origin_station_id', 'destination_station_id']));

        return view('admin.routes.index', [
            'routes' => $routes,
            'filters' => $request->only(['search', 'trashed', 'origin_station_id', 'destination_station_id']),
            'stations' => $this->stations->all(),
        ]);
    }

    public function create(): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.routes.create', ['stations' => $this->stations->all()]);
    }

    public function store(RouteRequest $request): RedirectResponse
    {
        $this->routes->create($request->validated());

        return redirect()->route('admin.routes.index')->with('status', 'Route created.');
    }

    public function edit(Route $route): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.routes.edit', [
            'route' => $route,
            'stations' => $this->stations->all(),
            'schedules' => $this->schedules->forRoute($route->id),
        ]);
    }

    public function update(RouteRequest $request, Route $route): RedirectResponse
    {
        $this->routes->update($route, $request->validated());

        return redirect()->route('admin.routes.index')->with('status', 'Route updated.');
    }

    public function destroy(Route $route): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->routes->delete($route);

        return redirect()->route('admin.routes.index')->with('status', 'Route deleted.');
    }

    public function restore(int $route): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->routes->restore($route);

        return redirect()->route('admin.routes.index')->with('status', 'Route restored.');
    }

    public function forceDelete(int $route): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->routes->forceDelete($route);

        return redirect()->route('admin.routes.index')->with('status', 'Route permanently deleted.');
    }
}
