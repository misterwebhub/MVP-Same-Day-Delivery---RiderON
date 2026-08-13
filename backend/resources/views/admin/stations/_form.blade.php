@php $s = $station ?? null; @endphp

<div>
    <label for="city_id" class="form-label">City</label>
    <select id="city_id" name="city_id" required class="form-input">
        <option value="">Select a city</option>
        @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((int) old('city_id', $s?->city_id) === $city->id)>{{ $city->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $s?->name) }}" required class="form-input">
</div>

<div>
    <label for="code" class="form-label">Code</label>
    <input id="code" type="text" name="code" value="{{ old('code', $s?->code) }}" required class="form-input">
</div>

<div>
    <label for="type" class="form-label">Type</label>
    <select id="type" name="type" required class="form-input">
        <option value="railway" @selected(old('type', $s?->type) === 'railway')>Railway</option>
        <option value="bus_stand" @selected(old('type', $s?->type) === 'bus_stand')>Bus stand</option>
    </select>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="latitude" class="form-label">Latitude</label>
        <input id="latitude" type="number" step="any" name="latitude" value="{{ old('latitude', $s?->latitude) }}" class="form-input">
    </div>
    <div>
        <label for="longitude" class="form-label">Longitude</label>
        <input id="longitude" type="number" step="any" name="longitude" value="{{ old('longitude', $s?->longitude) }}" class="form-input">
    </div>
</div>

<div>
    <label for="address" class="form-label">Address</label>
    <textarea id="address" name="address" rows="3" class="form-input">{{ old('address', $s?->address) }}</textarea>
</div>

<label class="flex items-center gap-2 text-caption text-text-secondary">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $s?->is_active ?? true)) class="rounded border-border text-primary focus:ring-primary/40">
    Active
</label>
