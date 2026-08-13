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
@endsection
