@extends('admin.layouts.app')

@section('title', 'Add city')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.cities.index') }}" class="link-quiet">&larr; Back to cities</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Add city</h2>
            <p class="page-subtitle">Create a new delivery city.</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.cities.store') }}" class="space-y-4">
            @csrf
            @include('admin.cities._form', ['city' => null])
            <button type="submit" class="btn-primary">Create city</button>
        </form>
    </div>
@endsection
