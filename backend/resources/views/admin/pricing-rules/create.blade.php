@extends('admin.layouts.app')

@section('title', 'Add pricing rule')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.pricing-rules.index') }}" class="link-quiet">&larr; Back to pricing rules</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Add pricing rule</h2>
            <p class="page-subtitle">Create a new fare, surcharge, or tax rule.</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.pricing-rules.store') }}" class="space-y-4">
            @csrf
            @include('admin.pricing-rules._form', ['pricingRule' => null, 'routes' => $routes])
            <button type="submit" class="btn-primary">Create pricing rule</button>
        </form>
    </div>
@endsection
