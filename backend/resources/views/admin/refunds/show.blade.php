@extends('admin.layouts.app')

@section('title', 'Refund #' . $refund->id)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.refunds.index') }}" class="link-quiet">&larr; Back to refunds</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Refund #{{ $refund->id }}</h2>
            <p class="page-subtitle">{{ ucfirst($refund->requested_by) }} request &middot; {{ $refund->created_at }}</p>
        </div>
        <span class="badge bg-info/10 text-info">{{ ucfirst($refund->status) }}</span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <h3 class="mb-4 text-body-strong text-text-primary">Refund details</h3>

            <dl class="grid grid-cols-2 gap-4 text-body">
                <div>
                    <dt class="text-caption text-text-secondary">Order</dt>
                    <dd>
                        @if ($refund->order)
                            <a href="{{ route('admin.orders.show', $refund->order) }}" class="text-primary hover:underline">{{ $refund->order->booking_reference }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Payment #</dt>
                    <dd>{{ $refund->payment_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Requested by</dt>
                    <dd>{{ ucfirst($refund->requested_by) }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Status</dt>
                    <dd class="badge bg-info/10 text-info">{{ ucfirst($refund->status) }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Amount</dt>
                    <dd>₹{{ number_format($refund->amount_paise / 100, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Provider refund ID</dt>
                    <dd>{{ $refund->provider_refund_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Approved by</dt>
                    <dd>{{ $refund->approvedBy?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-secondary">Created</dt>
                    <dd>{{ $refund->created_at }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-caption text-text-secondary">Reason</dt>
                    <dd>{{ $refund->reason }}</dd>
                </div>
            </dl>
        </div>

        @if (\App\Support\AdminAccess::canApproveRefunds() && $refund->status === \App\Models\Refund::STATUS_PENDING)
            <div class="space-y-4">
                <div class="action-card border-l-primary">
                    <h3 class="text-body-strong text-text-primary">Approve &amp; process</h3>
                    <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" onsubmit="return confirm('Approve and process this refund via the payment gateway?');">
                        @csrf
                        <button type="submit" class="btn-primary w-full">Approve &amp; process</button>
                    </form>
                </div>
                <div class="action-card border-l-danger">
                    <h3 class="text-body-strong text-text-primary">Reject</h3>
                    <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" onsubmit="return confirm('Reject this refund request?');">
                        @csrf
                        <button type="submit" class="btn-secondary w-full text-danger">Reject</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
