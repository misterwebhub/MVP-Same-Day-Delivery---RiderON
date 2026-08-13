@extends('admin.layouts.app')

@section('title', 'Edit route')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.routes.index') }}" class="link-quiet">&larr; Back to routes</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit route</h2>
            <p class="page-subtitle">{{ $route->originStation?->name }} &rarr; {{ $route->destinationStation?->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.routes.update', $route) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.routes._form', ['route' => $route, 'stations' => $stations])
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>

    <div class="mt-8">
        <div class="page-header">
            <div>
                <h2 class="page-title">Schedules</h2>
                <p class="page-subtitle">Departure times and days of operation for this route.</p>
            </div>
            @if (\App\Support\AdminAccess::canManageMasterData())
                <a href="{{ route('admin.routes.schedules.create', $route) }}" class="btn-primary">Add schedule</a>
            @endif
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Days</th>
                        <th>Cutoff (min)</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $dayLabels = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
                    @endphp
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td>{{ $schedule->departure_time }}</td>
                            <td>{{ $schedule->arrival_time }}</td>
                            <td>{{ collect($schedule->days_of_week)->map(fn ($d) => $dayLabels[(int) $d] ?? $d)->implode(', ') }}</td>
                            <td>{{ $schedule->booking_cutoff_minutes_before }}</td>
                            <td>
                                <span class="badge {{ $schedule->is_active ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                                    {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if (\App\Support\AdminAccess::canManageMasterData())
                                    <a href="{{ route('admin.routes.schedules.edit', [$route, $schedule]) }}" class="link-quiet">Edit</a>
                                    <form method="POST" action="{{ route('admin.routes.schedules.destroy', [$route, $schedule]) }}" class="inline" onsubmit="return confirm('Remove this schedule?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 link-quiet text-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">No schedules yet.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
