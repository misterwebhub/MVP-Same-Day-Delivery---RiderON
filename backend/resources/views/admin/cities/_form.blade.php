@php $c = $city ?? null; @endphp

<div>
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $c?->name) }}" required class="form-input">
</div>

<div>
    <label for="state" class="form-label">State</label>
    <input id="state" type="text" name="state" value="{{ old('state', $c?->state) }}" required class="form-input">
</div>

<label class="flex items-center gap-2 text-caption text-text-secondary">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $c?->is_active ?? true)) class="rounded border-border text-primary focus:ring-primary/40">
    Active
</label>
