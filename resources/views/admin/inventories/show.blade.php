@extends('layouts.admin')

@section('title','Inventory Details')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Inventory Details</h1>
        <div class="text-muted">{{ $inventory->productVariant?->product?->name }} / {{ $inventory->productVariant?->sku }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.inventories.adjust', $inventory) }}" class="btn btn-primary">Adjust Stock</a>
        <a href="{{ route('admin.inventories.edit', $inventory) }}" class="btn btn-success">Edit</a>
        <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Stock Summary</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Product Variant</dt>
                    <dd class="col-sm-8">{{ $inventory->productVariant?->product?->name }} / {{ $inventory->productVariant?->variant_name }}</dd>
                    <dt class="col-sm-4">SKU</dt>
                    <dd class="col-sm-8">{{ $inventory->productVariant?->sku }}</dd>
                    <dt class="col-sm-4">Stock Location</dt>
                    <dd class="col-sm-8">{{ $inventory->stockLocation?->name }} / {{ $inventory->stockLocation?->code }}</dd>
                    <dt class="col-sm-4">Quantity On Hand</dt>
                    <dd class="col-sm-8">{{ number_format((float) $inventory->quantity_on_hand, 3) }}</dd>
                    <dt class="col-sm-4">Reserved Quantity</dt>
                    <dd class="col-sm-8">{{ number_format((float) $inventory->reserved_quantity, 3) }}</dd>
                    <dt class="col-sm-4">Damaged Quantity</dt>
                    <dd class="col-sm-8">{{ number_format((float) $inventory->damaged_quantity, 3) }}</dd>
                    <dt class="col-sm-4">Available Quantity</dt>
                    <dd class="col-sm-8 fw-semibold">{{ number_format($inventory->available_quantity, 3) }}</dd>
                    <dt class="col-sm-4">Low Stock Threshold</dt>
                    <dd class="col-sm-8">{{ $inventory->low_stock_threshold ?? 'None' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Status</div>
            <div class="card-body">
                <div class="mb-2"><span class="badge {{ $inventory->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $inventory->status ? 'Active' : 'Inactive' }}</span></div>
                <div>
                    @if ($inventory->is_low_stock)
                        <span class="badge text-bg-danger">Low Stock</span>
                    @else
                        <span class="badge text-bg-light border">Stock OK</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Movement History</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Balance After</th>
                    <th>Note</th>
                    <th>Created By</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td><span class="badge text-bg-light border">{{ str_replace('_', ' ', $movement->movement_type) }}</span></td>
                        <td>{{ number_format((float) $movement->quantity, 3) }}</td>
                        <td>{{ number_format((float) $movement->balance_after, 3) }}</td>
                        <td>{{ $movement->note ?? 'None' }}</td>
                        <td>{{ $movement->creator?->name ?? 'System' }}</td>
                        <td>{{ $movement->created_at?->format('d M Y, h:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No movements recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $movements->links() }}</div>
</div>

@endsection
