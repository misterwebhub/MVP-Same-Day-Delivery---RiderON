@extends('admin.layouts.app')

@section('title', 'Add delivery partner')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.delivery-partners.index') }}" class="link-quiet">&larr; Back to delivery partners</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Add delivery partner</h2>
            <p class="page-subtitle">Create a new rider account.</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.delivery-partners.store') }}" class="space-y-4">
            @csrf
            @include('admin.delivery-partners._form', ['partner' => null, 'users' => $users, 'cities' => $cities])
            <button type="submit" class="btn-primary">Create delivery partner</button>
        </form>
    </div>
@endsection
