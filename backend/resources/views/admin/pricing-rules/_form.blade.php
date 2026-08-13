@php
    $p = $pricingRule ?? null;
    $ruleTypes = [
        \App\Models\PricingRule::TYPE_BASE => 'Base fare',
        \App\Models\PricingRule::TYPE_WEIGHT_SLAB => 'Weight slab',
        \App\Models\PricingRule::TYPE_PEAK_HOUR => 'Peak hour surcharge',
        \App\Models\PricingRule::TYPE_PLATFORM_FEE => 'Platform fee',
        \App\Models\PricingRule::TYPE_TAX => 'Tax',
    ];
    $selectedType = old('rule_type', $p?->rule_type);
@endphp

<div>
    <label for="route_id" class="form-label">Route</label>
    <select id="route_id" name="route_id" class="form-input">
        <option value="">— Global (all routes) —</option>
        @foreach ($routes as $route)
            <option value="{{ $route->id }}" @selected((string) old('route_id', $p?->route_id) === (string) $route->id)>
                {{ $route->originStation?->name }} &rarr; {{ $route->destinationStation?->name }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-caption text-text-secondary">Leave empty for a global rule applied to all routes without a more specific override.</p>
</div>

<div>
    <label for="rule_type" class="form-label">Rule type</label>
    <select id="rule_type" name="rule_type" required class="form-input" onchange="pricingRuleTypeChanged(this.value)">
        <option value="">Select a type</option>
        @foreach ($ruleTypes as $value => $label)
            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div id="weight-slab-fields" class="grid grid-cols-2 gap-4" style="display: none;">
    <div>
        <label for="min_weight_grams" class="form-label">Min weight (grams)</label>
        <input id="min_weight_grams" type="number" min="0" name="min_weight_grams" value="{{ old('min_weight_grams', $p?->min_weight_grams) }}" class="form-input">
    </div>
    <div>
        <label for="max_weight_grams" class="form-label">Max weight (grams)</label>
        <input id="max_weight_grams" type="number" min="0" name="max_weight_grams" value="{{ old('max_weight_grams', $p?->max_weight_grams) }}" class="form-input">
    </div>
</div>

<div>
    <label for="amount_paise" class="form-label">Amount (paise)</label>
    <input id="amount_paise" type="number" min="0" name="amount_paise" value="{{ old('amount_paise', $p?->amount_paise) }}" class="form-input">
    <p class="mt-1 text-caption text-text-secondary">Flat amount in paise. Used for base/weight_slab rules, or non-percentage peak_hour/platform_fee/tax rules.</p>
</div>

<div id="percentage-field" style="display: none;">
    <label for="percentage" class="form-label">Percentage</label>
    <input id="percentage" type="number" min="0" max="100" step="0.01" name="percentage" value="{{ old('percentage', $p?->percentage) }}" class="form-input">
    <p class="mt-1 text-caption text-text-secondary">Used instead of amount for percentage-based peak_hour/platform_fee/tax rules.</p>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="effective_from" class="form-label">Effective from</label>
        <input id="effective_from" type="datetime-local" name="effective_from" value="{{ old('effective_from', $p?->effective_from?->format('Y-m-d\TH:i')) }}" required class="form-input">
    </div>
    <div>
        <label for="effective_to" class="form-label">Effective to</label>
        <input id="effective_to" type="datetime-local" name="effective_to" value="{{ old('effective_to', $p?->effective_to?->format('Y-m-d\TH:i')) }}" class="form-input">
    </div>
</div>

<label class="flex items-center gap-2 text-caption text-text-secondary">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true)) class="rounded border-border text-primary focus:ring-primary/40">
    Active
</label>

<script>
    function pricingRuleTypeChanged(value) {
        const weightSlab = document.getElementById('weight-slab-fields');
        const percentage = document.getElementById('percentage-field');
        weightSlab.style.display = value === 'weight_slab' ? 'grid' : 'none';
        percentage.style.display = ['peak_hour', 'platform_fee', 'tax'].includes(value) ? 'block' : 'none';
    }
    pricingRuleTypeChanged(document.getElementById('rule_type').value);
</script>
