@extends('admin.layouts.app')

@section('title', 'Routes')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-title">Routes</h2>
            <p class="page-subtitle">Manage delivery routes between stations.</p>
        </div>
        @if (\App\Support\AdminAccess::canManageMasterData())
            <a href="{{ route('admin.routes.create') }}" class="btn-primary">Add route</a>
        @endif
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <select name="origin_station_id" class="form-input w-48">
                <option value="">Any origin</option>
                @foreach ($stations as $station)
                    <option value="{{ $station->id }}" @selected(($filters['origin_station_id'] ?? null) == $station->id)>{{ $station->name }}</option>
                @endforeach
            </select>
            <select name="destination_station_id" class="form-input w-48">
                <option value="">Any destination</option>
                @foreach ($stations as $station)
                    <option value="{{ $station->id }}" @selected(($filters['destination_station_id'] ?? null) == $station->id)>{{ $station->name }}</option>
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
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Distance (km)</th>
                    <th>Duration (min)</th>
                    <th>Cutoff</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($routes as $route)
                    <tr>
                        <td>{{ $route->originStation?->name }}</td>
                        <td>{{ $route->destinationStation?->name }}</td>
                        <td>{{ $route->distance_km }}</td>
                        <td>{{ $route->estimated_duration_minutes }}</td>
                        <td>{{ $route->cutoff_time }}</td>
                        <td>
                            <span class="badge {{ $route->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                {{ $route->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if (\App\Support\AdminAccess::canManageMasterData())
                                @if ($route->trashed())
                                    <form method="POST" action="{{ route('admin.routes.restore', $route->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="link-quiet text-info">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.routes.force-delete', $route->id) }}" class="inline" onsubmit="return confirm('Permanently delete this route? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 link-quiet text-danger">Force delete</button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.routes.edit', $route) }}" class="link-quiet">Edit / Schedules</a>
                                    <form method="POST" action="{{ route('admin.routes.destroy', $route) }}" class="inline" onsubmit="return confirm('Delete this route?');">
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
                        <td colspan="7">
                            <div class="empty-state">No routes found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $routes->links() }}
    </div>
@endsection
