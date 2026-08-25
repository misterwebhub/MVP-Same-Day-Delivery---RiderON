@php
    $p = $partner ?? null;
    $vehicleTypes = [
        \App\Models\DeliveryPartner::VEHICLE_TRAIN => 'Train',
        \App\Models\DeliveryPartner::VEHICLE_BUS => 'Bus',
        \App\Models\DeliveryPartner::VEHICLE_BIKE => 'Bike',
        \App\Models\DeliveryPartner::VEHICLE_ON_FOOT => 'On foot',
    ];
    $verificationStatuses = [
        \App\Models\DeliveryPartner::VERIFICATION_PENDING => 'Pending',
        \App\Models\DeliveryPartner::VERIFICATION_VERIFIED => 'Verified',
        \App\Models\DeliveryPartner::VERIFICATION_REJECTED => 'Rejected',
    ];
@endphp

@if ($p === null)
    {{-- Only offered on create: editing a partner never changes which login
         it's attached to, so the new-user fields only make sense here. --}}
    <div class="flex items-center gap-4 text-caption text-text-secondary">
        <label class="flex items-center gap-2">
            <input type="radio" name="create_new_user" value="0" onchange="document.getElementById('existing-user-fields').classList.remove('hidden'); document.getElementById('new-user-fields').classList.add('hidden');" @checked(! old('create_new_user', false))>
            Attach to an existing user
        </label>
        <label class="flex items-center gap-2">
            <input type="radio" name="create_new_user" value="1" onchange="document.getElementById('existing-user-fields').classList.add('hidden'); document.getElementById('new-user-fields').classList.remove('hidden');" @checked(old('create_new_user', false))>
            Create a brand-new rider login
        </label>
    </div>
@endif

<div id="existing-user-fields" class="{{ $p === null && old('create_new_user', false) ? 'hidden' : '' }}">
    <label for="user_id" class="form-label">Partner user account</label>
    <select id="user_id" name="user_id" class="form-input">
        <option value="">Select a user</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('user_id', $p?->user_id) === (string) $user->id)>
                {{ $user->name }} ({{ $user->phone }})
            </option>
        @endforeach
    </select>
</div>

@if ($p === null)
    <div id="new-user-fields" class="space-y-4 {{ old('create_new_user', false) ? '' : 'hidden' }}">
        <div>
            <label for="new_user_name" class="form-label">Rider name</label>
            <input id="new_user_name" type="text" name="new_user_name" value="{{ old('new_user_name') }}" class="form-input">
        </div>
        <div>
            <label for="new_user_phone" class="form-label">Rider mobile number</label>
            <input id="new_user_phone" type="text" name="new_user_phone" value="{{ old('new_user_phone') }}" placeholder="9876543210" class="form-input">
        </div>
        <div>
            <label for="new_user_password" class="form-label">Rider password</label>
            <input id="new_user_password" type="text" name="new_user_password" value="{{ old('new_user_password') }}" placeholder="Min. 8 characters" class="form-input">
            <p class="text-caption text-text-secondary mt-1">Share this password with the rider directly — it won't be shown again.</p>
        </div>
    </div>
@endif

<div>
    <label for="partner_code" class="form-label">Partner code</label>
    <input id="partner_code" type="text" name="partner_code" value="{{ old('partner_code', $p?->partner_code) }}" required class="form-input">
</div>

<div>
    <label for="photo_url" class="form-label">Photo URL</label>
    <input id="photo_url" type="text" name="photo_url" value="{{ old('photo_url', $p?->photo_url) }}" class="form-input">
</div>

<div>
    <label for="vehicle_type" class="form-label">Vehicle type</label>
    <select id="vehicle_type" name="vehicle_type" required class="form-input">
        @foreach ($vehicleTypes as $value => $label)
            <option value="{{ $value }}" @selected(old('vehicle_type', $p?->vehicle_type) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="id_proof_type" class="form-label">ID proof type</label>
        <input id="id_proof_type" type="text" name="id_proof_type" value="{{ old('id_proof_type', $p?->id_proof_type) }}" class="form-input">
    </div>
    <div>
        <label for="verification_status" class="form-label">Verification status</label>
        <select id="verification_status" name="verification_status" required class="form-input">
            @foreach ($verificationStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('verification_status', $p?->verification_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label for="id_proof_number_encrypted" class="form-label">ID proof number</label>
    <textarea id="id_proof_number_encrypted" name="id_proof_number_encrypted" class="form-input">{{ old('id_proof_number_encrypted', $p?->id_proof_number_encrypted) }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="rating_avg" class="form-label">Rating (0–5)</label>
        <input id="rating_avg" type="number" min="0" max="5" step="0.01" name="rating_avg" value="{{ old('rating_avg', $p?->rating_avg ?? 0) }}" required class="form-input">
    </div>
    <div>
        <label for="completed_deliveries_count" class="form-label">Completed deliveries</label>
        <input id="completed_deliveries_count" type="number" min="0" name="completed_deliveries_count" value="{{ old('completed_deliveries_count', $p?->completed_deliveries_count ?? 0) }}" required class="form-input">
    </div>
</div>

<div>
    <label for="current_home_city_id" class="form-label">Current home city</label>
    <select id="current_home_city_id" name="current_home_city_id" class="form-input">
        <option value="">— None —</option>
        @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((string) old('current_home_city_id', $p?->current_home_city_id) === (string) $city->id)>
                {{ $city->name }}
            </option>
        @endforeach
    </select>
</div>

<label class="flex items-center gap-2 text-caption text-text-secondary">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true)) class="rounded border-border text-primary focus:ring-primary/40">
    Active
</label>
