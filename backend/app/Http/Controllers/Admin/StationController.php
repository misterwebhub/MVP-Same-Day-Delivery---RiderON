<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StationRequest;
use App\Models\Station;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\StationRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StationController extends Controller
{
    public function __construct(
        private readonly StationRepositoryInterface $stations,
        private readonly CityRepositoryInterface $cities,
    ) {}

    public function index(Request $request): View
    {
        $stations = $this->stations->paginate(15, $request->only(['search', 'trashed', 'city_id']));

        return view('admin.stations.index', [
            'stations' => $stations,
            'filters' => $request->only(['search', 'trashed', 'city_id']),
            'cities' => $this->cities->all(),
        ]);
    }

    public function create(): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.stations.create', ['cities' => $this->cities->all()]);
    }

    public function store(StationRequest $request): RedirectResponse
    {
        $this->stations->create($request->validated());

        return redirect()->route('admin.stations.index')->with('status', 'Station created.');
    }

    public function edit(Station $station): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.stations.edit', ['station' => $station, 'cities' => $this->cities->all()]);
    }

    public function update(StationRequest $request, Station $station): RedirectResponse
    {
        $this->stations->update($station, $request->validated());

        return redirect()->route('admin.stations.index')->with('status', 'Station updated.');
    }

    public function destroy(Station $station): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->stations->delete($station);

        return redirect()->route('admin.stations.index')->with('status', 'Station deleted.');
    }

    public function restore(int $station): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->stations->restore($station);

        return redirect()->route('admin.stations.index')->with('status', 'Station restored.');
    }

    public function forceDelete(int $station): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->stations->forceDelete($station);

        return redirect()->route('admin.stations.index')->with('status', 'Station permanently deleted.');
    }
}
