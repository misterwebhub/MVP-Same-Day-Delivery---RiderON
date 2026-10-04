@php $r = $route ?? null; @endphp

<div>
    <label for="origin_station_id" class="form-label">Origin station</label>
    <select id="origin_station_id" name="origin_station_id" required class="form-input">
        <option value="">Select a station</option>
        @foreach ($stations as $station)
            <option value="{{ $station->id }}" @selected((int) old('origin_station_id', $r?->origin_station_id) === $station->id)>{{ $station->name }} ({{ $station->city?->name }})</option>
        @endforeach
    </select>
</div>

<div>
    <label for="destination_station_id" class="form-label">Destination station</label>
    <select id="destination_station_id" name="destination_station_id" required class="form-input">
        <option value="">Select a station</option>
        @foreach ($stations as $station)
            <option value="{{ $station->id }}" @selected((int) old('destination_station_id', $r?->destination_station_id) === $station->id)>{{ $station->name }} ({{ $station->city?->name }})</option>
        @endforeach
    </select>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="distance_km" class="form-label">Distance (km)</label>
        <input id="distance_km" type="number" step="0.01" min="0" name="distance_km" value="{{ old('distance_km', $r?->distance_km) }}" required class="form-input">
    </div>
    <div>
        <label for="estimated_duration_minutes" class="form-label">Estimated duration (minutes)</label>
        <input id="estimated_duration_minutes" type="number" min="1" name="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', $r?->estimated_duration_minutes) }}" required class="form-input">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="cutoff_time" class="form-label">Cutoff time</label>
        <input id="cutoff_time" type="time" name="cutoff_time" value="{{ old('cutoff_time', $r?->cutoff_time) }}" required class="form-input">
    </div>
    <div>
        <label for="max_parcels_per_schedule" class="form-label">Max parcels per schedule</label>
        <input id="max_parcels_per_schedule" type="number" min="1" name="max_parcels_per_schedule" value="{{ old('max_parcels_per_schedule', $r?->max_parcels_per_schedule ?? 50) }}" required class="form-input">
    </div>
</div>

<div>
    <label for="waiting_time_minutes" class="form-label">Waiting time (minutes)</label>
    <input id="waiting_time_minutes" type="number" min="0" name="waiting_time_minutes" value="{{ old('waiting_time_minutes', $r?->waiting_time_minutes ?? 30) }}" required class="form-input">
</div>

<label class="flex items-center gap-2 text-caption text-text-secondary">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $r?->is_active ?? true)) class="rounded border-border text-primary focus:ring-primary/40">
    Active
</label>
