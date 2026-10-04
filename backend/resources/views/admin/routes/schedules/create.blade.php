@extends('admin.layouts.app')

@section('title', 'Add schedule')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.routes.edit', $route) }}" class="link-quiet">&larr; Back to route</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Add schedule</h2>
            <p class="page-subtitle">{{ $route->originStation?->name }} &rarr; {{ $route->destinationStation?->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.routes.schedules.store', $route) }}" class="space-y-4">
            @csrf
            @include('admin.routes.schedules._form', ['schedule' => null])
            <button type="submit" class="btn-primary">Create schedule</button>
        </form>
    </div>
@endsection
