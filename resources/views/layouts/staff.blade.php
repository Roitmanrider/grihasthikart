@extends('layouts.app')

@section('content')
@php($staffPermissionService = app(\App\Domains\Staff\Services\StaffPermissionService::class))
<div class="min-vh-100 bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-success" href="{{ route('staff.dashboard') }}">GrihasthiKart Staff</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#staffNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="staffNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('staff.dashboard') }}">Dashboard</a></li>
                    @if ($staffPermissionService->has(auth()->user(), 'picking.view'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('staff.picking.index') }}">Picking</a></li>
                    @endif
                    @if ($staffPermissionService->has(auth()->user(), 'packing.view'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('staff.packing.index') }}">Packing</a></li>
                    @endif
                    @if ($staffPermissionService->has(auth()->user(), 'delivery.view'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('staff.deliveries.index') }}">My Deliveries</a></li>
                    @endif
                    @if ($staffPermissionService->has(auth()->user(), 'approvals.view'))
                        <li class="nav-item"><a class="nav-link" href="{{ route('staff.approvals.index') }}">Approvals</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link" href="{{ route('staff.notifications.index') }}">Notifications</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted">{{ auth()->user()?->assignedStore?->name ?: 'All Stores' }}</span>
                    <form method="POST" action="{{ route('staff.logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    <main class="container-fluid py-4">
        @include('partials.flash')
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @yield('staff-content')
    </main>
</div>
@endsection
