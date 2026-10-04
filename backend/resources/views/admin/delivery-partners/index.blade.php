@extends('admin.layouts.app')

@section('title', 'Delivery partners')

@section('content')
    @php
        $verificationColors = [
            'pending' => 'bg-warning/10 text-warning',
            'verified' => 'bg-success/10 text-success',
            'rejected' => 'bg-danger/10 text-danger',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">Delivery partners</h2>
            <p class="page-subtitle">Manage rider accounts, verification, and availability.</p>
        </div>
        @if (\App\Support\AdminAccess::canManageOrders())
            <a href="{{ route('admin.delivery-partners.create') }}" class="btn-primary">Add delivery partner</a>
        @endif
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search partner code" class="form-input w-56">
            <select name="verification_status" class="form-input w-40">
                <option value="">All statuses</option>
                <option value="pending" @selected(($filters['verification_status'] ?? '') === 'pending')>Pending</option>
                <option value="verified" @selected(($filters['verification_status'] ?? '') === 'verified')>Verified</option>
                <option value="rejected" @selected(($filters['verification_status'] ?? '') === 'rejected')>Rejected</option>
            </select>
            <select name="trashed" class="form-input w-40">
                <option value="">Active only</option>
                <option value="with" @selected(($filters['trashed'] ?? null) === 'with')>With trashed</option>
                <option value="only" @selected(($filters['trashed'] ?? null) === 'only')>Trashed only</option>
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Code</th>
                    <th>Vehicle</th>
                    <th>Verification</th>
                    <th>Rating</th>
                    <th>Deliveries</th>
                    <th>Home city</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($partners as $partner)
                    <tr>
                        <td>{{ $partner->user?->name }}</td>
                        <td>{{ $partner->partner_code }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $partner->vehicle_type)) }}</td>
                        <td>
                            <span class="badge {{ $verificationColors[$partner->verification_status] ?? 'bg-border text-text-secondary' }}">
                                {{ ucfirst($partner->verification_status) }}
                            </span>
                        </td>
                        <td>{{ $partner->rating_avg }}</td>
                        <td>{{ $partner->completed_deliveries_count }}</td>
                        <td>{{ $partner->currentHomeCity?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $partner->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                {{ $partner->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if ($partner->trashed())
                                @if (\App\Support\AdminAccess::canManageMasterData())
                                    <form method="POST" action="{{ route('admin.delivery-partners.restore', $partner->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-quiet text-info">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.delivery-partners.force-delete', $partner->id) }}" class="inline" onsubmit="return confirm('Permanently delete this partner? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 link-quiet text-danger">Force delete</button>
                                    </form>
                                @endif
                            @else
                                @if (\App\Support\AdminAccess::canManageOrders())
                                    <a href="{{ route('admin.delivery-partners.edit', $partner) }}" class="link-quiet">Edit</a>
                                @endif
                                @if (\App\Support\AdminAccess::canManageMasterData())
                                    <form method="POST" action="{{ route('admin.delivery-partners.destroy', $partner) }}" class="inline" onsubmit="return confirm('Delete this delivery partner?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 link-quiet text-danger">Delete</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">No delivery partners found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $partners->links() }}
    </div>
@endsection
