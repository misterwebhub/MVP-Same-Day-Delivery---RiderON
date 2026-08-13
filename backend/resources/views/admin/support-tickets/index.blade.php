@extends('admin.layouts.app')

@section('title', 'Support tickets')

@section('content')
    @php
        $statusColors = [
            'open' => 'bg-danger/10 text-danger',
            'in_progress' => 'bg-warning/10 text-warning',
            'resolved' => 'bg-success/10 text-success',
            'closed' => 'bg-success/10 text-success',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">Support tickets</h2>
            <p class="page-subtitle">Respond to and resolve customer support requests.</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search ticket number" class="form-input w-56">
            <select name="status" class="form-input w-40">
                <option value="">All statuses</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>In progress</option>
                <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Resolved</option>
                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
            </select>
            <select name="category" class="form-input w-48">
                <option value="">All categories</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Customer</th>
                    <th>Order</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Assigned to</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="font-medium">{{ $ticket->ticket_number }}</td>
                        <td>{{ $ticket->customer?->name }}</td>
                        <td>{{ $ticket->order?->booking_reference ?? '—' }}</td>
                        <td><span class="badge bg-border text-text-secondary">{{ $categories[$ticket->category] ?? $ticket->category }}</span></td>
                        <td>
                            <span class="badge {{ $statusColors[$ticket->status] ?? 'bg-border text-text-secondary' }}">
                                {{ $categories[$ticket->status] ?? ucwords(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</td>
                        <td>{{ $ticket->created_at }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.support-tickets.edit', $ticket) }}" class="link-quiet">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">No support tickets found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
@endsection
