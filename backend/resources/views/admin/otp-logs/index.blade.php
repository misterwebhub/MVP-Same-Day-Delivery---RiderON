@extends('admin.layouts.app')

@section('title', 'OTP verification logs')

@section('content')
    @php
        $resultColors = [
            'success' => 'bg-success/10 text-success',
            'invalid' => 'bg-danger/10 text-danger',
            'expired' => 'bg-warning/10 text-warning',
            'locked' => 'bg-danger/10 text-danger',
        ];
    @endphp

    <div class="page-header">
        <div>
            <h2 class="page-title">OTP verification logs</h2>
            <p class="page-subtitle">Audit trail of OTP verification attempts.</p>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="flex items-center gap-2">
            <select name="result" class="form-input w-40">
                <option value="">All results</option>
                <option value="success" @selected(($filters['result'] ?? '') === 'success')>Success</option>
                <option value="invalid" @selected(($filters['result'] ?? '') === 'invalid')>Invalid</option>
                <option value="expired" @selected(($filters['result'] ?? '') === 'expired')>Expired</option>
                <option value="locked" @selected(($filters['result'] ?? '') === 'locked')>Locked</option>
            </select>
            <select name="attempted_by_type" class="form-input w-40">
                <option value="">All actors</option>
                <option value="customer" @selected(($filters['attempted_by_type'] ?? '') === 'customer')>Customer</option>
                <option value="partner" @selected(($filters['attempted_by_type'] ?? '') === 'partner')>Partner</option>
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table>
            <thead>
                <tr>
                    <th>Phone</th>
                    <th>Purpose</th>
                    <th>Attempted by</th>
                    <th>Result</th>
                    <th>IP address</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->otpVerification?->phone ?? '—' }}</td>
                        <td>{{ ucfirst($log->otpVerification?->purpose ?? '—') }}</td>
                        <td><span class="badge bg-border text-text-secondary">{{ ucfirst($log->attempted_by_type) }}</span></td>
                        <td>
                            <span class="badge {{ $resultColors[$log->result] ?? 'bg-border text-text-secondary' }}">
                                {{ ucfirst($log->result) }}
                            </span>
                        </td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                        <td>{{ $log->created_at }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">No OTP verification logs found.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endsection
