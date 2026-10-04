<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryPartnerRequest;
use App\Http\Requests\Admin\ResetPartnerPasswordRequest;
use App\Models\DeliveryPartner;
use App\Models\User;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Contracts\DeliveryPartnerRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        $data = $request->validated();

        // "create_new_user" branch: DeliveryPartnerRequest already enforced
        // (via prohibited/required rules) that either user_id XOR the
        // new_user_* trio is present, so this is the only place that needs
        // to know about the distinction — everything downstream just sees a
        // user_id.
        if ($data['create_new_user'] ?? false) {
            $newUser = User::query()->create([
                'name' => $data['new_user_name'],
                'phone' => $data['new_user_phone'],
                'password' => Hash::make($data['new_user_password']),
                'role' => User::ROLE_PARTNER,
                'status' => User::STATUS_ACTIVE,
            ]);

            $data['user_id'] = $newUser->id;
        }

        unset($data['create_new_user'], $data['new_user_name'], $data['new_user_phone'], $data['new_user_password']);

        $this->partners->create($data);

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

    /**
     * Sets a new password on the partner's login (User row), independent of
     * the delivery_partner profile fields — there was previously no admin
     * path to recover a rider who forgot their password, since partner
     * login is phone+password (no OTP fallback) and partners have no
     * self-service "forgot password" flow yet either.
     */
    public function resetPassword(ResetPartnerPasswordRequest $request, DeliveryPartner $deliveryPartner): RedirectResponse
    {
        $user = $deliveryPartner->user;

        abort_if($user === null, 422, 'This delivery partner has no linked login account.');

        $user->forceFill(['password' => Hash::make($request->validated('password'))])->save();

        return redirect()
            ->route('admin.delivery-partners.edit', $deliveryPartner)
            ->with('status', "Password reset for {$user->name} ({$user->phone}).");
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
