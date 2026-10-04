@extends('admin.layouts.app')

@section('title', 'Recheck Orders')

@section('content')
    @php
        $statusColors = [
            'COMPLETED' => 'bg-success/10 text-success',
            'DELIVERED' => 'bg-success/10 text-success',
            'CANCELLED' => 'bg-danger/10 text-danger',
            'PAYMENT_FAILED' => 'bg-danger/10 text-danger',
            'DISPUTED' => 'bg-danger/10 text-danger',
            'FAILED_DELIVERY' => 'bg-danger/10 text-danger',
            'REFUND_PENDING' => 'bg-warning/10 text-warning',
            'REFUNDED' => 'bg-warning/10 text-warning',
            'RIDER_ASSIGNMENT_PENDING' => 'bg-warning/10 text-warning',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">Recheck Orders</h2>
            <p class="page-subtitle">Only orders that already have a rider assigned — defaults to today, pick any other date to audit.</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="booking_date" value="{{ $filters['booking_date'] }}" class="form-input w-40">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search reference / phone" class="form-input w-56">
            <button type="submit" class="btn-secondary">Filter</button>
            <a href="{{ route('admin.orders.recheck') }}" class="link-quiet">Reset to today</a>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Partner</th>
                    <th>Status</th>
                    <th>Booking date</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-medium">{{ $order->booking_reference }}</td>
                        <td>{{ $order->customer?->name }}</td>
                        <td>
                            {{ $order->route?->originStation?->name ?? '—' }} → {{ $order->route?->destinationStation?->name ?? '—' }}
                        </td>
                        <td>{{ $order->partner?->user?->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $statusColors[$order->status] ?? 'bg-info/10 text-info' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ optional($order->booking_date)->format('Y-m-d') }}</td>
                        <td>₹{{ number_format($order->total_amount_paise / 100, 2) }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="link-quiet">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">No assigned orders for this date.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
@endsection
