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
        ['label' => 'Low Stock Items', 'value' => $lowStockItems, 'icon' => 'fa-triangle-exclamation', 'route' => route('admin.inventory.replenishment.index', ['stock_status' => 'reorder_needed'])],
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
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                <div class="fw-semibold">Low Stock Replenishment</div>
                <a href="{{ route('admin.inventory.replenishment.index', ['stock_status' => 'reorder_needed']) }}" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body">
                @forelse ($lowStockPreview as $inventory)
                    <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom pb-3 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $inventory->productVariant?->product?->name }}</div>
                            <div class="small text-muted">{{ $inventory->productVariant?->variant_name }} / {{ $inventory->productVariant?->sku }} / {{ $inventory->stockLocation?->name }}</div>
                            <div class="small">Sellable: {{ number_format((float) $inventory->available_quantity, 3) }} / Reorder: {{ $inventory->reorder_level ?? 'None' }} / Target: {{ $inventory->target_stock_level ?? 'Not configured' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $inventory->stock_status === 'OUT_OF_STOCK' ? 'text-bg-danger' : 'text-bg-warning' }}">{{ str($inventory->stock_status)->replace('_', ' ')->headline() }}</span>
                            <div class="small text-muted mt-1">Buy: {{ $inventory->recommended_purchase_quantity === null ? 'Not configured' : number_format((float) $inventory->recommended_purchase_quantity, 3) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">No reorder-needed inventory records.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                <div class="fw-semibold">Addresses Pending Approval</div>
                <span class="badge text-bg-warning rounded-pill px-3 py-2">{{ $pendingAddressCount }}</span>
            </div>
            <div class="card-body">
                @forelse ($pendingAddresses as $address)
                    <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom pb-3 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $address->customer?->name }} <span class="text-muted small">{{ $address->customer?->mobile }}</span></div>
                            <div class="small">{{ $address->label ?: 'Address' }}</div>
                            <div class="small text-muted">{{ $address->address_line1 }}, {{ $address->city }} - {{ $address->pincode }}</div>
                        </div>
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <a href="{{ route('admin.customers.show', $address->customer) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <form method="POST" action="{{ route('admin.customers.addresses.approve', [$address->customer, $address]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-success">Approve</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">No addresses are waiting for approval.</div>
                @endforelse

                <a href="{{ route('admin.customers.index', ['pending_addresses' => 1]) }}" class="btn btn-outline-success btn-sm">View All Pending Addresses</a>
            </div>
        </div>
    </div>
</div>

@endsection
