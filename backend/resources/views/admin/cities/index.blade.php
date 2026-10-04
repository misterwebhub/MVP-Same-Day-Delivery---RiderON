@extends('admin.layouts.app')

@section('title', 'Cities')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-title">Cities</h2>
            <p class="page-subtitle">Manage delivery cities and coverage areas.</p>
        </div>
        @if (\App\Support\AdminAccess::canManageMasterData())
            <a href="{{ route('admin.cities.create') }}" class="btn-primary">Add city</a>
        @endif
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or state" class="form-input w-64">
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
                    <th>Name</th>
                    <th>State</th>
                    <th>Active</th>
                    <th>Created</th>
                    <th>Deleted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cities as $city)
                    <tr>
                        <td>{{ $city->name }}</td>
                        <td>{{ $city->state }}</td>
                        <td>
                            <span class="badge {{ $city->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                {{ $city->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-caption text-text-secondary">{{ $city->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-caption text-text-secondary">{{ $city->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-right">
                            @if (\App\Support\AdminAccess::canManageMasterData())
                                @if ($city->trashed())
                                    <form method="POST" action="{{ route('admin.cities.restore', $city->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-quiet text-info">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.cities.force-delete', $city->id) }}" class="inline" onsubmit="return confirm('Permanently delete this city? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 link-quiet text-danger">Force delete</button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.cities.edit', $city) }}" class="link-quiet">Edit</a>
                                    <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" class="inline" onsubmit="return confirm('Delete this city?');">
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
                        <td colspan="6">
                            <div class="empty-state">No cities found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $cities->links() }}
    </div>
@endsection
