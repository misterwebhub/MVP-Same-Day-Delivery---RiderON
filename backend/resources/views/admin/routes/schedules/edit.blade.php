@extends('admin.layouts.app')

@section('title', 'Edit schedule')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.routes.edit', $route) }}" class="link-quiet">&larr; Back to route</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit schedule</h2>
            <p class="page-subtitle">{{ $route->originStation?->name }} &rarr; {{ $route->destinationStation?->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.routes.schedules.update', [$route, $schedule]) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.routes.schedules._form', ['schedule' => $schedule])
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
@endsection
