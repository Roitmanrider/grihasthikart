@extends('layouts.staff')

@section('title', 'Staff Dashboard')

@section('staff-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Staff Dashboard</h1>
        <div class="text-muted">Your operational queues and notification counts.</div>
    </div>
</div>

<div class="row g-3">
    @foreach (['manager' => 'Manager', 'picking' => 'Picking', 'packing' => 'Packing', 'delivery' => 'Delivery', 'approvals' => 'Approvals', 'cart_followup' => 'Cart Follow-up'] as $key => $label)
        <div class="col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h4 mb-0">{{ (int) ($counts[$key] ?? 0) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Active Assigned Tasks</div><div class="h4 mb-0">{{ $tasks }}</div></div></div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Approvals Awaiting You</div><div class="h4 mb-0">{{ $approvals }}</div></div></div>
    </div>
</div>
@endsection
