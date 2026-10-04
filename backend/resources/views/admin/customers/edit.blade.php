@extends('admin.layouts.app')

@section('title', 'Edit customer')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.customers.index') }}" class="link-quiet">&larr; Back to customers</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit customer</h2>
            <p class="page-subtitle">{{ $customer->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="form-label">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $customer->name) }}" required class="form-input">
            </div>

            <div>
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
            </div>

            <div>
                <label for="phone" class="form-label">Phone</label>
                <input id="phone" type="text" value="{{ $customer->phone }}" disabled class="form-input bg-border/30">
                <p class="mt-1 text-caption text-text-secondary">Phone number is read-only and cannot be changed here.</p>
            </div>

            <div>
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" required class="form-input">
                    <option value="active" @selected(old('status', $customer->status) === 'active')>Active</option>
                    <option value="suspended" @selected(old('status', $customer->status) === 'suspended')>Suspended</option>
                    <option value="deleted" @selected(old('status', $customer->status) === 'deleted')>Deleted</option>
                </select>
            </div>

            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
@endsection
