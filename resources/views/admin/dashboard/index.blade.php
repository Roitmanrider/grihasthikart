@extends('layouts.admin')

@section('title','Dashboard')

@section('admin-content')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Admin Dashboard</h1>
        <div class="text-muted">MVP operations snapshot for catalog, orders, payments, inventory, and cashback.</div>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-success">Review Orders</a>
</div>

<div class="row g-4">
    @foreach ([
        ['label' => 'Products', 'value' => $totalProducts, 'icon' => 'fa-boxes-stacked', 'route' => route('admin.products.index')],
        ['label' => 'Orders', 'value' => $totalOrders, 'icon' => 'fa-receipt', 'route' => route('admin.orders.index')],
        ['label' => 'Pending Orders', 'value' => $pendingOrders, 'icon' => 'fa-clock', 'route' => route('admin.orders.index', ['order_status' => 'placed'])],
        ['label' => 'Low Stock Items', 'value' => $lowStockItems, 'icon' => 'fa-triangle-exclamation', 'route' => route('admin.inventories.index', ['stock' => 'low'])],
        ['label' => 'Pending Payments', 'value' => $pendingPayments, 'icon' => 'fa-indian-rupee-sign', 'route' => route('admin.payments.index', ['payment_status' => 'pending'])],
        ['label' => 'Cashback Requests', 'value' => $pendingCashbackRedemptions, 'icon' => 'fa-gift', 'route' => route('admin.cashback.redemptions.index', ['status' => 'pending'])],
    ] as $card)
        <div class="col-md-6 col-xl-4">
            <a href="{{ $card['route'] }}" class="text-decoration-none text-dark">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h3 mb-0">{{ $card['value'] }}</div>
                        </div>
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fa-solid {{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Quick Actions</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('admin.products.create') }}" class="btn btn-outline-success">Add Product</a>
                <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-success">Manage Inventory</a>
                <a href="{{ route('admin.settings.checkout.edit') }}" class="btn btn-outline-secondary">Checkout Settings</a>
                <a href="{{ route('admin.reports.gst-summary') }}" class="btn btn-outline-secondary">GST Report</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">MVP Readiness</div>
            <div class="card-body">
                <div class="small text-muted">Use the sidebar to review each module before demo. Reports and settings are admin-only and protected by gates.</div>
            </div>
        </div>
    </div>
</div>

@endsection
