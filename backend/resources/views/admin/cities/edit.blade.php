@extends('admin.layouts.app')

@section('title', 'Edit city')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.cities.index') }}" class="link-quiet">&larr; Back to cities</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit city</h2>
            <p class="page-subtitle">{{ $city->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.cities.update', $city) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.cities._form', ['city' => $city])
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
@endsection
