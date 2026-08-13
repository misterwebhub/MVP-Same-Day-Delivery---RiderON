@php
    $s = $schedule ?? null;
    $dayLabels = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    $selectedDays = old('days_of_week', $s?->days_of_week ?? []);
@endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="departure_time" class="form-label">Departure time</label>
        <input id="departure_time" type="time" name="departure_time" value="{{ old('departure_time', $s?->departure_time) }}" required class="form-input">
    </div>
    <div>
        <label for="arrival_time" class="form-label">Arrival time</label>
        <input id="arrival_time" type="time" name="arrival_time" value="{{ old('arrival_time', $s?->arrival_time) }}" required class="form-input">
    </div>
</div>

<div>
    <span class="form-label">Days of week</span>
    <div class="grid grid-cols-4 gap-2">
        @foreach ($dayLabels as $value => $label)
            <label class="flex items-center gap-2 text-caption text-text-secondary">
                <input type="checkbox" name="days_of_week[]" value="{{ $value }}" @checked(in_array($value, array_map('intval', $selectedDays), true)) class="rounded border-border text-primary focus:ring-primary/40">
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

<div>
    <label for="booking_cutoff_minutes_before" class="form-label">Booking cutoff (minutes before departure)</label>
    <input id="booking_cutoff_minutes_before" type="number" min="0" name="booking_cutoff_minutes_before" value="{{ old('booking_cutoff_minutes_before', $s?->booking_cutoff_minutes_before ?? 60) }}" required class="form-input">
</div>

<label class="flex items-center gap-2 text-caption text-text-secondary">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $s?->is_active ?? true)) class="rounded border-border text-primary focus:ring-primary/40">
    Active
</label>
