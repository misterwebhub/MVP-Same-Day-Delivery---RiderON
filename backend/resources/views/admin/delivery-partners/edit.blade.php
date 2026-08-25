@extends('admin.layouts.app')

@section('title', 'Edit delivery partner')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.delivery-partners.index') }}" class="link-quiet">&larr; Back to delivery partners</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit delivery partner</h2>
            <p class="page-subtitle">{{ $partner->user?->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.delivery-partners.update', $partner) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.delivery-partners._form', ['partner' => $partner, 'users' => $users, 'cities' => $cities])
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>

    @if ($partner->user)
        <div class="card max-w-lg mt-6">
            <h3 class="text-body font-semibold mb-1">Reset rider password</h3>
            <p class="page-subtitle mb-4">
                Sets a new login password for {{ $partner->user->name }} ({{ $partner->user->phone }}).
                Use this if the rider is locked out — partner login has no self-service "forgot password" yet.
            </p>
            <form method="POST" action="{{ route('admin.delivery-partners.reset-password', $partner) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="password" class="form-label">New password</label>
                    <input id="password" type="text" name="password" required minlength="8" class="form-input">
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input id="password_confirmation" type="text" name="password_confirmation" required minlength="8" class="form-input">
                </div>
                <button type="submit" class="btn-primary">Reset password</button>
            </form>
        </div>
    @endif
@endsection
