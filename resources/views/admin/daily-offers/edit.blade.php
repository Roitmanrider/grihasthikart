@extends('layouts.admin')

@section('title', 'Edit Daily Offer')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Daily Offer</h1>
            <div class="text-muted">Update homepage Daily Offer details.</div>
        </div>
        <a href="{{ route('admin.daily-offers.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.daily-offers.update', $dailyOffer) }}" class="card border-0 shadow-sm">
        @csrf
        @method('PATCH')
        <div class="card-body row g-3">
            @include('admin.daily-offers.form')
        </div>
    </form>
@endsection
