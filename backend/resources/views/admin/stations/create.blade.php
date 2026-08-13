@extends('admin.layouts.app')

@section('title', 'Add station')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.stations.index') }}" class="link-quiet">&larr; Back to stations</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Add station</h2>
            <p class="page-subtitle">Create a new railway or bus stand station.</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.stations.store') }}" class="space-y-4">
            @csrf
            @include('admin.stations._form', ['station' => null, 'cities' => $cities])
            <button type="submit" class="btn-primary">Create station</button>
        </form>
    </div>
@endsection
