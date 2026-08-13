@extends('admin.layouts.app')

@section('title', 'Edit station')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.stations.index') }}" class="link-quiet">&larr; Back to stations</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit station</h2>
            <p class="page-subtitle">{{ $station->name }}</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.stations.update', $station) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.stations._form', ['station' => $station, 'cities' => $cities])
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
@endsection
