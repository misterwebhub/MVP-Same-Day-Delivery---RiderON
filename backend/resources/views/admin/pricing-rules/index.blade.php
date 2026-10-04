@extends('admin.layouts.app')

@section('title', 'Pricing rules')

@section('content')
    @php
        $ruleTypeLabels = [
            'base' => 'Base fare',
            'weight_slab' => 'Weight slab',
            'peak_hour' => 'Peak hour surcharge',
            'platform_fee' => 'Platform fee',
            'tax' => 'Tax',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">Pricing rules</h2>
            <p class="page-subtitle">Manage fare, surcharge, and tax rules across routes.</p>
        </div>
        @if (\App\Support\AdminAccess::canManagePricing())
            <a href="{{ route('admin.pricing-rules.create') }}" class="btn-primary">Add pricing rule</a>
        @endif
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <select name="route_id" class="form-input w-56">
                <option value="">All routes</option>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}" @selected(($filters['route_id'] ?? '') == $route->id)>
                        {{ $route->originStation?->name }} &rarr; {{ $route->destinationStation?->name }}
                    </option>
                @endforeach
            </select>
            <select name="rule_type" class="form-input w-48">
                <option value="">All types</option>
                @foreach ($ruleTypeLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['rule_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Route</th>
                    <th>Type</th>
                    <th>Weight range</th>
                    <th>Amount</th>
                    <th>Percentage</th>
                    <th>Effective from</th>
                    <th>Effective to</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pricingRules as $rule)
                    <tr>
                        <td>
                            @if ($rule->route)
                                {{ $rule->route->originStation?->name }} &rarr; {{ $rule->route->destinationStation?->name }}
                            @else
                                <span class="text-text-secondary">Global</span>
                            @endif
                        </td>
                        <td>{{ $ruleTypeLabels[$rule->rule_type] ?? $rule->rule_type }}</td>
                        <td>
                            @if ($rule->min_weight_grams !== null || $rule->max_weight_grams !== null)
                                {{ $rule->min_weight_grams ?? 0 }}g &ndash; {{ $rule->max_weight_grams ?? '∞' }}g
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $rule->amount_paise !== null ? '₹' . number_format($rule->amount_paise / 100, 2) : '—' }}</td>
                        <td>{{ $rule->percentage !== null ? $rule->percentage . '%' : '—' }}</td>
                        <td class="text-caption text-text-secondary">{{ $rule->effective_from?->format('Y-m-d H:i') }}</td>
                        <td class="text-caption text-text-secondary">{{ $rule->effective_to?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $rule->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if (\App\Support\AdminAccess::canManagePricing())
                                <a href="{{ route('admin.pricing-rules.edit', $rule) }}" class="link-quiet">Edit</a>
                                <form method="POST" action="{{ route('admin.pricing-rules.destroy', $rule) }}" class="inline" onsubmit="return confirm('Delete this pricing rule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 link-quiet text-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">No pricing rules found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $pricingRules->links() }}
    </div>
@endsection
