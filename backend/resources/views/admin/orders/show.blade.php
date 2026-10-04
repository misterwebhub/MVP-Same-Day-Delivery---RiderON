@extends('admin.layouts.app')

@section('title', 'Order ' . $order->booking_reference)

@section('content')
    @php
        $statusColors = [
            \App\Constants\OrderStatus::PAYMENT_PENDING => 'bg-warning/10 text-warning',
            \App\Constants\OrderStatus::RIDER_ASSIGNMENT_PENDING => 'bg-info/10 text-info',
            \App\Constants\OrderStatus::DISPUTED => 'bg-danger/10 text-danger',
            \App\Constants\OrderStatus::REFUND_PENDING => 'bg-warning/10 text-warning',
            \App\Constants\OrderStatus::CANCELLED => 'bg-danger/10 text-danger',
            \App\Constants\OrderStatus::DELIVERED => 'bg-success/10 text-success',
            \App\Constants\OrderStatus::COMPLETED => 'bg-success/10 text-success',
        ];
        $statusColor = $statusColors[$order->status] ?? 'bg-border text-text-secondary';
    @endphp

    <div class="mb-4">
        <a href="{{ route('admin.orders.index') }}" class="link-quiet inline-flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to orders
        </a>
    </div>

    <div class="page-header">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-h1 font-heading text-text-primary">{{ $order->booking_reference }}</h2>
                <span class="badge {{ $statusColor }}">{{ str_replace('_', ' ', $order->status) }}</span>
            </div>
            <p class="page-subtitle">Placed {{ optional($order->booking_date)->format('D, d M Y') }} &middot; {{ $order->customer?->name }}</p>
        </div>
        <div class="flex gap-3">
            <div class="chip">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2"/><circle cx="12" cy="12" r="9"/></svg>
                ₹{{ number_format($order->total_amount_paise / 100, 2) }}
            </div>
            <div class="chip">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M1 3h13v13H1V3zm13 5h4l3 3v5h-7V8z"/></svg>
                {{ $order->partner?->user?->name ?? 'Unassigned' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <h3 class="mb-4 text-body-strong text-text-primary">Order details</h3>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-body sm:grid-cols-2">
                    <div>
                        <dt class="text-caption text-text-secondary">Customer</dt>
                        <dd class="font-medium">{{ $order->customer?->name }} <span class="font-normal text-text-secondary">({{ $order->customer?->phone }})</span></dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-secondary">Route</dt>
                        <dd class="font-medium">{{ $order->route?->originStation?->name ?? '—' }} <span class="text-text-secondary">&rarr;</span> {{ $order->route?->destinationStation?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-secondary">Sender</dt>
                        <dd class="font-medium">{{ $order->sender_name }} <span class="font-normal text-text-secondary">&middot; {{ $order->sender_phone }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-secondary">Receiver</dt>
                        <dd class="font-medium">{{ $order->receiver_name }} <span class="font-normal text-text-secondary">&middot; {{ $order->receiver_phone }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-secondary">Amount</dt>
                        <dd class="font-medium">₹{{ number_format($order->total_amount_paise / 100, 2) }} {{ $order->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-caption text-text-secondary">Booking date</dt>
                        <dd class="font-medium">{{ optional($order->booking_date)->format('Y-m-d') }}</dd>
                    </div>
                    @if ($order->cancelled_at)
                        <div>
                            <dt class="text-caption text-text-secondary">Cancelled at</dt>
                            <dd class="font-medium text-danger">{{ $order->cancelled_at }} <span class="font-normal text-text-secondary">({{ $order->cancelled_by }})</span></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-caption text-text-secondary">Cancellation reason</dt>
                            <dd class="font-medium">{{ $order->cancellation_reason }}</dd>
                        </div>
                    @endif
                    @if ($order->delivered_at)
                        <div>
                            <dt class="text-caption text-text-secondary">Delivered at</dt>
                            <dd class="font-medium text-success">{{ $order->delivered_at }}</dd>
                        </div>
                    @endif
                    @if ($order->completed_at)
                        <div>
                            <dt class="text-caption text-text-secondary">Completed at</dt>
                            <dd class="font-medium text-success">{{ $order->completed_at }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="card">
                <h3 class="mb-4 text-body-strong text-text-primary">Status history</h3>
                @if ($order->statusHistory->isEmpty())
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-text-secondary/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        No history yet.
                    </div>
                @else
                    <div class="timeline">
                        @foreach ($order->statusHistory as $entry)
                            <div class="timeline-item">
                                <span class="timeline-dot">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>
                                </span>
                                <div class="flex-1 pt-0.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($entry->from_status)
                                            <span class="text-caption text-text-secondary">{{ str_replace('_', ' ', $entry->from_status) }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        @endif
                                        <span class="text-body-strong text-text-primary">{{ str_replace('_', ' ', $entry->to_status) }}</span>
                                    </div>
                                    <p class="mt-0.5 text-caption text-text-secondary">by {{ ucfirst($entry->changed_by_type) }} &middot; {{ $entry->created_at }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card">
                <h3 class="mb-4 text-body-strong text-text-primary">Activity &amp; evidence</h3>
                <p class="mb-4 text-caption text-text-secondary">Every notable event on this order — who did it, from what IP, and (rider-side only) GPS distance from the relevant station. Admin-only; never shown to the customer or partner apps.</p>
                @if ($order->activityLogs->isEmpty())
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-text-secondary/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        No activity recorded yet.
                    </div>
                @else
                    <div class="timeline">
                        @foreach ($order->activityLogs as $log)
                            <div class="timeline-item">
                                <span class="timeline-dot">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>
                                </span>
                                <div class="flex-1 pt-0.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-body-strong text-text-primary">{{ str_replace('_', ' ', $log->event) }}</span>
                                        <span class="text-caption text-text-secondary">by {{ ucfirst($log->actor_type) }}{{ $log->actor ? ' · '.$log->actor->name : '' }}</span>
                                    </div>
                                    <p class="mt-0.5 text-caption text-text-secondary">{{ $log->created_at }}</p>
                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-caption text-text-secondary">
                                        @if ($log->ip_address)
                                            <span>IP: <span class="font-mono text-text-primary">{{ $log->ip_address }}</span></span>
                                        @endif
                                        @if ($log->latitude !== null && $log->longitude !== null)
                                            <span>GPS: <span class="font-mono text-text-primary">{{ $log->latitude }}, {{ $log->longitude }}</span></span>
                                        @endif
                                        @if ($log->distance_from_target_meters !== null)
                                            <span class="{{ $log->distance_from_target_meters > 500 ? 'text-warning font-medium' : '' }}">{{ $log->distance_from_target_meters }}m from station</span>
                                        @endif
                                    </div>
                                    @if ($log->metadata)
                                        <p class="mt-0.5 text-caption text-text-secondary">{{ json_encode($log->metadata) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($order->pickup_proof_photo_path || $order->delivery_proof_photo_path)
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @if ($order->pickup_proof_photo_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->pickup_proof_photo_path) }}" target="_blank" class="block">
                                <p class="mb-1 text-micro uppercase tracking-wide text-text-secondary">Pickup proof</p>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->pickup_proof_photo_path) }}" class="h-24 w-full rounded-md border border-border object-cover" alt="Pickup proof photo">
                            </a>
                        @endif
                        @if ($order->delivery_proof_photo_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->delivery_proof_photo_path) }}" target="_blank" class="block">
                                <p class="mb-1 text-micro uppercase tracking-wide text-text-secondary">Delivery proof</p>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($order->delivery_proof_photo_path) }}" class="h-24 w-full rounded-md border border-border object-cover" alt="Delivery proof photo">
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            @if (\App\Support\AdminAccess::canManageOrders())
                <div class="action-card border-l-secondary">
                    <h3 class="text-body-strong text-text-primary">Pickup &amp; delivery OTP</h3>
                    <p class="text-caption text-text-secondary">Generate a code instantly for testing — skips SMS delivery. Use it in the partner app to verify pickup/delivery.</p>

                    @if (session('generatedOtp'))
                        @php $g = session('generatedOtp'); @endphp
                        <div class="rounded-md border border-primary/30 bg-primary/5 p-3">
                            <p class="text-micro uppercase tracking-wide text-primary">{{ ucfirst($g['purpose']) }} OTP</p>
                            <p class="mt-1 font-mono text-h1 tracking-[0.3em] text-text-primary">{{ $g['otp'] }}</p>
                            <p class="mt-1 text-caption text-text-secondary">for {{ $g['phone'] }} &middot; expires {{ $g['expires_at']->format('H:i:s') }}</p>
                        </div>
                    @endif

                    <div class="space-y-2 text-caption">
                        <div class="flex items-center justify-between rounded-md border border-border px-3 py-2">
                            <div>
                                <p class="font-medium text-text-primary">Pickup</p>
                                @if ($latestPickupOtp)
                                    <p class="text-text-secondary">
                                        {{ $latestPickupOtp->verified_at ? 'Verified' : ($latestPickupOtp->expires_at->isPast() ? 'Expired' : 'Pending') }}
                                        &middot; {{ $latestPickupOtp->phone }}
                                    </p>
                                @else
                                    <p class="text-text-secondary">Not generated yet</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.orders.otp.generate', [$order, 'pickup']) }}">
                                @csrf
                                <button type="submit" class="btn-secondary">{{ $latestPickupOtp && ! $latestPickupOtp->verified_at ? 'Regenerate' : 'Generate' }}</button>
                            </form>
                        </div>

                        <div class="flex items-center justify-between rounded-md border border-border px-3 py-2">
                            <div>
                                <p class="font-medium text-text-primary">Delivery</p>
                                @if ($latestDeliveryOtp)
                                    <p class="text-text-secondary">
                                        {{ $latestDeliveryOtp->verified_at ? 'Verified' : ($latestDeliveryOtp->expires_at->isPast() ? 'Expired' : 'Pending') }}
                                        &middot; {{ $latestDeliveryOtp->phone }}
                                    </p>
                                @else
                                    <p class="text-text-secondary">Not generated yet</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.orders.otp.generate', [$order, 'delivery']) }}">
                                @csrf
                                <button type="submit" class="btn-secondary">{{ $latestDeliveryOtp && ! $latestDeliveryOtp->verified_at ? 'Regenerate' : 'Generate' }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if (\App\Support\AdminAccess::canManageOrders() && ! \App\Constants\OrderStatus::isTerminal($order->status))
                <div class="action-card border-l-info">
                    <h3 class="text-body-strong text-text-primary">{{ $order->partner_id === null ? 'Assign partner' : 'Reassign partner' }}</h3>
                    @if ($order->partner)
                        <p class="text-caption text-text-secondary mb-3">
                            Currently assigned: {{ $order->partner->user?->name }} ({{ $order->partner->partner_code }})
                        </p>
                    @endif
                    <form method="POST" action="{{ route('admin.orders.assign-partner', $order) }}" class="space-y-3">
                        @csrf
                        <select name="partner_id" required class="form-input">
                            <option value="">Select a partner</option>
                            @foreach ($eligiblePartners as $partner)
                                <option value="{{ $partner->id }}" @selected($partner->id === $order->partner_id)>{{ $partner->user?->name }} ({{ $partner->partner_code }}) — {{ $partner->completed_deliveries_count }} deliveries</option>
                            @endforeach
                        </select>
                        @if ($eligiblePartners->isEmpty())
                            <p class="text-caption text-warning">No eligible partners available right now.</p>
                        @endif
                        <button type="submit" class="btn-primary w-full">{{ $order->partner_id === null ? 'Assign' : 'Reassign' }}</button>
                    </form>
                </div>
            @endif

            @if (\App\Support\AdminAccess::canManageOrders() && ! \App\Constants\OrderStatus::isTerminal($order->status))
                <div class="action-card border-l-danger">
                    <h3 class="text-body-strong text-text-primary">Force cancel</h3>
                    <form method="POST" action="{{ route('admin.orders.force-cancel', $order) }}" class="space-y-3" onsubmit="return confirm('Force cancel this order?');">
                        @csrf
                        <textarea name="reason" required placeholder="Cancellation reason" class="form-input"></textarea>
                        <button type="submit" class="btn-secondary w-full text-danger">Cancel order</button>
                    </form>
                </div>
            @endif

            @if (\App\Support\AdminAccess::canManageOrders() && in_array($order->status, app(\App\StateMachines\OrderStateMachine::class)->allowedSourceStatuses(\App\Constants\OrderStatus::DISPUTED), true))
                <div class="action-card border-l-warning">
                    <h3 class="text-body-strong text-text-primary">Mark disputed</h3>
                    <form method="POST" action="{{ route('admin.orders.mark-disputed', $order) }}" class="space-y-3" onsubmit="return confirm('Mark this order disputed?');">
                        @csrf
                        <textarea name="reason" required placeholder="Dispute reason" class="form-input"></textarea>
                        <button type="submit" class="btn-secondary w-full text-warning">Mark disputed</button>
                    </form>
                </div>
            @endif

            @if (\App\Support\AdminAccess::canApproveRefunds() && $order->status === \App\Constants\OrderStatus::REFUND_PENDING)
                <div class="action-card border-l-primary">
                    <h3 class="text-body-strong text-text-primary">Approve refund</h3>
                    <form method="POST" action="{{ route('admin.orders.approve-refund', $order) }}" class="space-y-3" onsubmit="return confirm('Approve refund for this order?');">
                        @csrf
                        <input type="number" name="amount_paise" min="1" value="{{ $order->total_amount_paise }}" required class="form-input" placeholder="Refund amount (paise)">
                        <textarea name="reason" required placeholder="Refund reason" class="form-input"></textarea>
                        <button type="submit" class="btn-primary w-full">Approve refund</button>
                    </form>
                </div>
            @endif

            @if (! \App\Support\AdminAccess::canManageOrders() && ! \App\Support\AdminAccess::canApproveRefunds())
                <div class="card">
                    <p class="text-caption text-text-secondary">No actions available for your role on this order.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
