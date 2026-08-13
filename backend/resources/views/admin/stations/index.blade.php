@extends('admin.layouts.app')

@section('title', 'Stations')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-title">Stations</h2>
            <p class="page-subtitle">Manage railway and bus stand pickup points.</p>
        </div>
        @if (\App\Support\AdminAccess::canManageMasterData())
            <a href="{{ route('admin.stations.create') }}" class="btn-primary">Add station</a>
        @endif
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or code" class="form-input w-56">
            <select name="city_id" class="form-input w-44">
                <option value="">All cities</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected(($filters['city_id'] ?? null) == $city->id)>{{ $city->name }}</option>
                @endforeach
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
                    <th>City</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stations as $station)
                    <tr>
                        <td>{{ $station->city?->name }}</td>
                        <td>{{ $station->name }}</td>
                        <td>{{ $station->code }}</td>
                        <td>{{ str($station->type)->replace('_', ' ')->title() }}</td>
                        <td>
                            <span class="badge {{ $station->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                {{ $station->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if (\App\Support\AdminAccess::canManageMasterData())
                                @if ($station->trashed())
                                    <form method="POST" action="{{ route('admin.stations.restore', $station->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-quiet text-info">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.stations.force-delete', $station->id) }}" class="inline" onsubmit="return confirm('Permanently delete this station? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 link-quiet text-danger">Force delete</button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.stations.edit', $station) }}" class="link-quiet">Edit</a>
                                    <form method="POST" action="{{ route('admin.stations.destroy', $station) }}" class="inline" onsubmit="return confirm('Delete this station?');">
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
                            <div class="empty-state">No stations found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $stations->links() }}
    </div>
@endsection
