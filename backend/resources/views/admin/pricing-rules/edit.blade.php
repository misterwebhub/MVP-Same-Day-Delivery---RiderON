@extends('admin.layouts.app')

@section('title', 'Edit pricing rule')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.pricing-rules.index') }}" class="link-quiet">&larr; Back to pricing rules</a>
    </div>

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit pricing rule</h2>
            <p class="page-subtitle">Update fare, surcharge, or tax details.</p>
        </div>
    </div>

    <div class="card max-w-lg">
        <form method="POST" action="{{ route('admin.pricing-rules.update', $pricingRule) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.pricing-rules._form', ['pricingRule' => $pricingRule, 'routes' => $routes])
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>
@endsection
