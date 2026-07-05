@extends('layouts.admin')

@section('title','Inventory')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Inventory</h1>
        <div class="text-muted">Stock tracked by product variant and stock location.</div>
    </div>

    <a href="{{ route('admin.inventories.create') }}" class="btn btn-success">Add Inventory</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.inventories.index') }}" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Product, variant, SKU, barcode">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Variant</label>
                <select name="product_variant_id" class="form-select">
                    <option value="">All variants</option>
                    @foreach ($options['variants'] as $variant)
                        <option value="{{ $variant->id }}" @selected((string) request('product_variant_id') === (string) $variant->id)>
                            {{ $variant->product?->name }} / {{ $variant->variant_name }} / {{ $variant->sku }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Location</label>
                <select name="stock_location_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($options['locations'] as $location)
                        <option value="{{ $location->id }}" @selected((string) request('stock_location_id') === (string) $location->id)>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('status') === '1')>Active</option>
                    <option value="0" @selected(request('status') === '0')>Inactive</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Deleted</label>
                <select name="trashed" class="form-select">
                    <option value="">Without deleted</option>
                    <option value="with" @selected(request('trashed') === 'with')>With deleted</option>
                    <option value="only" @selected(request('trashed') === 'only')>Deleted only</option>
                </select>
            </div>
            <div class="col-lg-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="low_stock" @checked(request('low_stock') === '1')>
                    <label class="form-check-label" for="low_stock">Low stock only</label>
                </div>
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.inventories.bulk-action', request()->query()) }}">
    @csrf

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="action" class="form-select">
                        <option value="">Bulk action</option>
                        <option value="activate">Mark Active</option>
                        <option value="deactivate">Mark Inactive</option>
                        <option value="delete">Delete</option>
                        <option value="restore">Restore</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-outline-secondary">Apply</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.inventory-check').forEach((el) => el.checked = this.checked)"></th>
                        <th>Product / Variant</th>
                        <th>Location</th>
                        <th>On Hand</th>
                        <th>Reserved</th>
                        <th>Damaged</th>
                        <th>Available</th>
                        <th>Low Stock</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventories as $inventory)
                        <tr @class(['table-warning' => $inventory->trashed(), 'table-danger' => $inventory->is_low_stock && ! $inventory->trashed()])>
                            <td><input type="checkbox" name="ids[]" value="{{ $inventory->id }}" class="form-check-input inventory-check"></td>
                            <td>
                                <div class="fw-semibold">{{ $inventory->productVariant?->product?->name }}</div>
                                <div class="small text-muted">{{ $inventory->productVariant?->variant_name }} / {{ $inventory->productVariant?->sku }}</div>
                            </td>
                            <td>{{ $inventory->stockLocation?->name }}</td>
                            <td>{{ number_format((float) $inventory->quantity_on_hand, 3) }}</td>
                            <td>{{ number_format((float) $inventory->reserved_quantity, 3) }}</td>
                            <td>{{ number_format((float) $inventory->damaged_quantity, 3) }}</td>
                            <td class="fw-semibold">{{ number_format($inventory->available_quantity, 3) }}</td>
                            <td>
                                @if ($inventory->is_low_stock)
                                    <span class="badge text-bg-danger">Low</span>
                                @else
                                    <span class="badge text-bg-light border">OK</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $inventory->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $inventory->status ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                @if (! $inventory->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.inventories.show', $inventory) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.inventories.adjust', $inventory) }}" class="btn btn-outline-primary">Adjust</a>
                                        <a href="{{ route('admin.inventories.edit', $inventory) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($inventory->trashed())
                                    <form method="POST" action="{{ route('admin.inventories.restore', $inventory->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.inventories.destroy', $inventory) }}" class="d-inline" onsubmit="return confirm('Delete this inventory record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">No inventory records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">{{ $inventories->links() }}</div>
    </div>
</form>

@endsection
