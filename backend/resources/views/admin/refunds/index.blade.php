@extends('admin.layouts.app')

@section('title', 'Refunds')

@section('content')
    @php
        $statusColors = [
            'pending' => 'bg-warning/10 text-warning',
            'approved' => 'bg-info/10 text-info',
            'processing' => 'bg-info/10 text-info',
            'completed' => 'bg-success/10 text-success',
            'rejected' => 'bg-danger/10 text-danger',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">Refunds</h2>
            <p class="page-subtitle">Review and process customer refund requests.</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <select name="status" class="form-input w-48">
                <option value="">All statuses</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="processing" @selected(($filters['status'] ?? '') === 'processing')>Processing</option>
                <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
                <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Requested by</th>
                    <th>Reason</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Provider ref</th>
                    <th>Approved by</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refunds as $refund)
                    <tr>
                        <td>{{ $refund->order?->booking_reference }}</td>
                        <td><span class="badge bg-border text-text-secondary">{{ ucfirst($refund->requested_by) }}</span></td>
                        <td>{{ \Illuminate\Support\Str::limit($refund->reason, 40) }}</td>
                        <td>₹{{ number_format($refund->amount_paise / 100, 2) }}</td>
                        <td>
                            <span class="badge {{ $statusColors[$refund->status] ?? 'bg-border text-text-secondary' }}">
                                {{ ucfirst($refund->status) }}
                            </span>
                        </td>
                        <td>{{ $refund->provider_refund_id ?? '—' }}</td>
                        <td>{{ $refund->approvedBy?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.refunds.show', $refund) }}" class="link-quiet">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">No refunds found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $refunds->links() }}
    </div>
@endsection
