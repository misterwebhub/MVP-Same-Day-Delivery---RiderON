@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
    @php
        $statusColors = [
            'active' => 'bg-success/10 text-success',
            'suspended' => 'bg-danger/10 text-danger',
            'deleted' => 'bg-border text-text-secondary',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">Customers</h2>
            <p class="page-subtitle">View and manage registered customer accounts.</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, phone, or email" class="form-input w-64">
            <select name="status" class="form-input w-40">
                <option value="">All statuses</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspended</option>
                <option value="deleted" @selected(($filters['status'] ?? '') === 'deleted')>Deleted</option>
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Orders</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->email ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $statusColors[$customer->status] ?? 'bg-border text-text-secondary' }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </td>
                        <td>{{ $customer->ordersAsCustomer()->count() }}</td>
                        <td class="text-caption text-text-secondary">{{ $customer->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-right">
                            @if (\App\Support\AdminAccess::canManageMasterData())
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="link-quiet">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">No customers found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $customers->links() }}
    </div>
@endsection
