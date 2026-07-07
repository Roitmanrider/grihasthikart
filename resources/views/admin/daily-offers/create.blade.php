@extends('layouts.admin')

@section('title', 'Create Daily Offer')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Daily Offer</h1>
            <div class="text-muted">Add a product variant to homepage Daily Offers.</div>
        </div>
        <a href="{{ route('admin.daily-offers.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.daily-offers.store') }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body row g-3">
            @include('admin.daily-offers.form', ['dailyOffer' => null])
        </div>
    </form>
@endsection
