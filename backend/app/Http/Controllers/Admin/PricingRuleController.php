<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PricingRuleRequest;
use App\Models\PricingRule;
use App\Repositories\Contracts\PricingRuleRepositoryInterface;
use App\Repositories\Contracts\RouteRepositoryInterface;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingRuleController extends Controller
{
    public function __construct(
        private readonly PricingRuleRepositoryInterface $pricingRules,
        private readonly RouteRepositoryInterface $routes,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(AdminAccess::canManageMasterData(), 403);

        $pricingRules = $this->pricingRules->paginate(15, $request->only(['route_id', 'rule_type']));

        return view('admin.pricing-rules.index', [
            'pricingRules' => $pricingRules,
            'routes' => $this->routes->all(),
            'filters' => $request->only(['route_id', 'rule_type']),
        ]);
    }

    public function create(): View
    {
        abort_unless(AdminAccess::canManagePricing(), 403);

        return view('admin.pricing-rules.create', ['routes' => $this->routes->all()]);
    }

    public function store(PricingRuleRequest $request): RedirectResponse
    {
        $this->pricingRules->create($request->validated());

        return redirect()->route('admin.pricing-rules.index')->with('status', 'Pricing rule created.');
    }

    public function edit(PricingRule $pricingRule): View
    {
        abort_unless(AdminAccess::canManagePricing(), 403);

        return view('admin.pricing-rules.edit', [
            'pricingRule' => $pricingRule,
            'routes' => $this->routes->all(),
        ]);
    }

    public function update(PricingRuleRequest $request, PricingRule $pricingRule): RedirectResponse
    {
        $this->pricingRules->update($pricingRule, $request->validated());

        return redirect()->route('admin.pricing-rules.index')->with('status', 'Pricing rule updated.');
    }

    public function destroy(PricingRule $pricingRule): RedirectResponse
    {
        abort_unless(AdminAccess::canManagePricing(), 403);

        $this->pricingRules->delete($pricingRule);

        return redirect()->route('admin.pricing-rules.index')->with('status', 'Pricing rule deleted.');
    }
}
