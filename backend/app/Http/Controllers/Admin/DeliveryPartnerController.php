<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryPartnerRequest;
use App\Models\DeliveryPartner;
use App\Models\User;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\DeliveryPartnerRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryPartnerController extends Controller
{
    public function __construct(
        private readonly DeliveryPartnerRepositoryInterface $partners,
        private readonly CityRepositoryInterface $cities,
    ) {}

    public function index(Request $request): View
    {
        $partners = $this->partners->paginate(15, $request->only(['search', 'trashed', 'verification_status', 'current_home_city_id']));

        return view('admin.delivery-partners.index', [
            'partners' => $partners,
            'filters' => $request->only(['search', 'trashed', 'verification_status', 'current_home_city_id']),
        ]);
    }

    public function create(): View
    {
        abort_unless(AdminAccess::canManageOrders(), 403);

        return view('admin.delivery-partners.create', [
            'users' => User::query()->where('role', User::ROLE_PARTNER)->orderBy('name')->get(),
            'cities' => $this->cities->all(),
        ]);
    }

    public function store(DeliveryPartnerRequest $request): RedirectResponse
    {
        $this->partners->create($request->validated());

        return redirect()->route('admin.delivery-partners.index')->with('status', 'Delivery partner created.');
    }

    public function edit(DeliveryPartner $deliveryPartner): View
    {
        abort_unless(AdminAccess::canManageOrders(), 403);

        return view('admin.delivery-partners.edit', [
            'partner' => $deliveryPartner,
            'users' => User::query()->where('role', User::ROLE_PARTNER)->orderBy('name')->get(),
            'cities' => $this->cities->all(),
        ]);
    }

    public function update(DeliveryPartnerRequest $request, DeliveryPartner $deliveryPartner): RedirectResponse
    {
        $this->partners->update($deliveryPartner, $request->validated());

        return redirect()->route('admin.delivery-partners.index')->with('status', 'Delivery partner updated.');
    }

    public function destroy(DeliveryPartner $deliveryPartner): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->partners->delete($deliveryPartner);

        return redirect()->route('admin.delivery-partners.index')->with('status', 'Delivery partner deleted.');
    }

    public function restore(int $deliveryPartner): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->partners->restore($deliveryPartner);

        return redirect()->route('admin.delivery-partners.index')->with('status', 'Delivery partner restored.');
    }

    public function forceDelete(int $deliveryPartner): RedirectResponse
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $this->partners->forceDelete($deliveryPartner);

        return redirect()->route('admin.delivery-partners.index')->with('status', 'Delivery partner permanently deleted.');
    }
}
