@extends('layouts.admin')

@section('title', 'Cashback')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div><h1 class="h3 mb-1">Cashback</h1><div class="text-muted">Process monthly cashback and manage redemption requests.</div></div>
    <a href="{{ route('admin.cashback.rules.index') }}" class="btn btn-outline-success">Rules</a>
</div>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Earned</div><div class="h4">Rs. {{ number_format($stats['earned'], 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Redeemed</div><div class="h4">Rs. {{ number_format($stats['redeemed'], 2) }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Pending Requests</div><div class="h4">{{ $stats['pending_redemptions'] }}</div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Customers</div><div class="h4">{{ $stats['customers_with_balance'] }}</div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Process Cashback</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.cashback.process') }}" class="row g-3">
            @csrf
            <div class="col-md-3"><input type="number" name="customer_id" class="form-control" placeholder="Customer ID optional"></div>
            <div class="col-md-3"><input type="number" name="month" value="{{ now()->subMonth()->month }}" min="1" max="12" class="form-control" required></div>
            <div class="col-md-3"><input type="number" name="year" value="{{ now()->subMonth()->year }}" class="form-control" required></div>
            <div class="col-md-3"><button class="btn btn-success w-100">Process</button></div>
        </form>
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('admin.cashback.redemptions.index') }}" class="btn btn-outline-secondary">View Redemptions</a>
</div>
@endsection
