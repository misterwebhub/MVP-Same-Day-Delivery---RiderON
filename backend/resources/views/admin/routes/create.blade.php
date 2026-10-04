@extends('admin.layouts.app')

@section('title', 'Add route')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.routes.index') }}" class="link-quiet">&larr; Back to routes</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Add route</h2>
            <p class="page-subtitle">Create a new delivery route between two stations.</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.routes.store') }}" class="space-y-4">
            @csrf
            @include('admin.routes._form', ['route' => null, 'stations' => $stations])
            <button type="submit" class="btn-primary">Create route</button>
        </form>
    </div>
@endsection
