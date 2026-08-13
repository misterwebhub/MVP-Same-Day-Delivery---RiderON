<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerRequest;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function index(Request $request): View
    {
        $customers = $this->customers->paginate(15, $request->only(['search', 'status']));

        return view('admin.customers.index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function edit(User $customer): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);
        abort_unless($customer->role === User::ROLE_CUSTOMER, 404);

        return view('admin.customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === User::ROLE_CUSTOMER, 404);

        $this->customers->update($customer, $request->validated());

        return redirect()->route('admin.customers.index')->with('status', 'Customer updated.');
    }
}
