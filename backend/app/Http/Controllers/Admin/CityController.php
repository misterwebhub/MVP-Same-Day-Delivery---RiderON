<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Models\City;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityController extends Controller
{
    public function __construct(private readonly CityRepositoryInterface $cities) {}

    public function index(Request $request): View
    {
        $cities = $this->cities->paginate(15, $request->only(['search', 'trashed']));

        return view('admin.cities.index', [
            'cities' => $cities,
            'filters' => $request->only(['search', 'trashed']),
        ]);
    }

    public function create(): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.cities.create');
    }

    public function store(CityRequest $request): RedirectResponse
    {
        $this->cities->create($request->validated());

        return redirect()->route('admin.cities.index')->with('status', 'City created.');
    }

    public function edit(City $city): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        return view('admin.cities.edit', ['city' => $city]);
    }

    public function update(CityRequest $request, City $city): RedirectResponse
    {
        $this->cities->update($city, $request->validated());

        return redirect()->route('admin.cities.index')->with('status', 'City updated.');
    }

    public function destroy(City $city): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->cities->delete($city);

        return redirect()->route('admin.cities.index')->with('status', 'City deleted.');
    }

    public function restore(int $city): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->cities->restore($city);

        return redirect()->route('admin.cities.index')->with('status', 'City restored.');
    }

    public function forceDelete(int $city): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->cities->forceDelete($city);

        return redirect()->route('admin.cities.index')->with('status', 'City permanently deleted.');
    }
}
