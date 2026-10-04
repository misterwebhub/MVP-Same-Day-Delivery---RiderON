@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-title">Welcome back, {{ explode(' ', $user->name)[0] }}</h2>
            <p class="page-subtitle">Here's what's happening across RiderON today.</p>
        </div>
        <div class="chip">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            {{ now()->format('D, d M Y') }}
        </div>
    </div>

    <div class="space-y-8">
        <section>
            <h2 class="mb-3 text-h2 font-heading text-text-primary">Today's bookings</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @include('admin.components.stat-card', ['label' => "Today's bookings", 'value' => $bookingsCount, 'icon' => 'chart'])
                @include('admin.components.stat-card', ['label' => "Today's revenue", 'value' => '₹'.number_format($revenuePaise / 100, 2), 'icon' => 'money'])
                @include('admin.components.stat-card', [
                    'label' => 'Awaiting partner assignment (today)',
                    'value' => $pendingAssignment,
                    'color' => $pendingAssignment > 0 ? 'warning' : 'success',
                    'icon' => 'clock',
                ])
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-h2 font-heading text-text-primary">Rider assignment</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @include('admin.components.stat-card', [
                    'label' => 'Total unassigned orders (all dates)',
                    'value' => $totalUnassigned,
                    'color' => $totalUnassigned > 0 ? 'warning' : 'success',
                    'icon' => 'clock',
                    'href' => route('admin.orders.index', ['status' => 'RIDER_ASSIGNMENT_PENDING']),
                ])
                @include('admin.components.stat-card', [
                    'label' => "Recheck today's assigned orders",
                    'value' => 'Open tab',
                    'icon' => 'check',
                    'href' => route('admin.orders.recheck'),
                ])
            </div>
            <p class="mt-2 text-caption text-text-secondary">No rider is ever auto-assigned on booking — every eligible partner sees these in their app's "Unassigned" tab and claims one themselves.</p>
        </section>

        <section>
            <h2 class="mb-3 text-h2 font-heading text-text-primary">Delivery funnel (today)</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @include('admin.components.stat-card', ['label' => 'Booked', 'value' => $funnel['booked'], 'icon' => 'chart'])
                @include('admin.components.stat-card', ['label' => 'Rider assigned', 'value' => $funnel['rider_assigned'], 'color' => 'info', 'icon' => 'truck'])
                @include('admin.components.stat-card', ['label' => 'In transit', 'value' => $funnel['in_transit'], 'color' => 'info', 'icon' => 'truck'])
                @include('admin.components.stat-card', ['label' => 'Delivered / completed', 'value' => $funnel['delivered_completed'], 'color' => 'success', 'icon' => 'check'])
                @include('admin.components.stat-card', ['label' => 'Exceptions', 'value' => $funnel['exceptions'], 'color' => 'danger', 'icon' => 'alert'])
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-h2 font-heading text-text-primary">Active riders</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @include('admin.components.stat-card', ['label' => 'Active partners', 'value' => $activePartners, 'icon' => 'user'])
                @include('admin.components.stat-card', ['label' => 'Verified partners', 'value' => $verifiedPartners, 'color' => 'success', 'icon' => 'shield'])
                @include('admin.components.stat-card', ['label' => 'Currently on a delivery', 'value' => $onDeliveryPartners, 'color' => 'info', 'icon' => 'truck'])
            </div>
        </section>

        <section>
            <div class="card flex items-center gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-h2 font-heading text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <div>
                    <p class="text-caption text-text-secondary">Signed in as</p>
                    <p class="text-h2 font-heading text-text-primary">{{ $user->name }} <span class="text-body font-normal text-text-secondary">({{ ucfirst($user->role) }})</span></p>
                </div>
            </div>
        </section>
    </div>
@endsection
