@extends('admin.layouts.app')

@section('title', 'Activity & notifications')

@section('content')
    <div class="page-header">
        <div>
            <h2 class="page-title">Activity & notifications</h2>
            <p class="page-subtitle">Everything the system has told a customer, partner, or admin — OTP regenerations, new assignments, and more.</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <select name="type" class="form-input w-56">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
            <select name="channel" class="form-input w-40">
                <option value="">All channels</option>
                <option value="push" @selected(($filters['channel'] ?? '') === 'push')>Push</option>
                <option value="in_app" @selected(($filters['channel'] ?? '') === 'in_app')>In-app (admin)</option>
                <option value="sms" @selected(($filters['channel'] ?? '') === 'sms')>SMS</option>
                <option value="whatsapp" @selected(($filters['channel'] ?? '') === 'whatsapp')>WhatsApp</option>
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Type</th>
                    <th>Recipient</th>
                    <th>Channel</th>
                    <th>Title / body</th>
                    <th>Delivered</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($notifications as $n)
                    <tr>
                        <td>{{ $n->created_at }}</td>
                        <td><span class="badge bg-border text-text-secondary">{{ ucfirst(str_replace('_', ' ', $n->type)) }}</span></td>
                        <td>{{ $n->user?->name ?? '—' }} <span class="text-text-secondary">({{ ucfirst($n->user?->role ?? '—') }})</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $n->channel)) }}</td>
                        <td>
                            <div class="text-body-strong">{{ $n->title }}</div>
                            <div class="text-caption text-text-secondary">{{ $n->body }}</div>
                        </td>
                        <td>
                            @if ($n->channel !== 'push')
                                <span class="badge bg-border text-text-secondary">N/A</span>
                            @elseif ($n->sent_at)
                                <span class="badge bg-success/10 text-success">Sent {{ $n->sent_at }}</span>
                            @else
                                <span class="badge bg-warning/10 text-warning">No device</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">No activity yet.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
@endsection
