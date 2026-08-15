@extends('layouts.admin')

@section('title', 'Inventory Replenishment')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Inventory Replenishment</h1>
        <div class="text-muted">Variant and location level reorder planning.</div>
    </div>
    <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">Inventory</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    @foreach ([
        ['label' => 'In Stock', 'value' => $summary['in_stock'], 'class' => 'text-bg-success'],
        ['label' => 'Low Stock', 'value' => $summary['low_stock'], 'class' => 'text-bg-warning'],
        ['label' => 'Out of Stock', 'value' => $summary['out_of_stock'], 'class' => 'text-bg-danger'],
        ['label' => 'Reorder Needed', 'value' => $summary['reorder_needed'], 'class' => 'text-bg-danger'],
        ['label' => 'No Target', 'value' => $summary['no_target_configured'], 'class' => 'text-bg-secondary'],
        ['label' => 'No Supplier', 'value' => $summary['no_supplier_assigned'], 'class' => 'text-bg-secondary'],
    ] as $stat)
        <div class="col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">{{ $stat['label'] }}</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="h4 mb-0">{{ $stat['value'] }}</div>
                        <span class="badge {{ $stat['class'] }}">&nbsp;</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.inventory.replenishment.index') }}" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Product, SKU, barcode">
            </div>
            <div class="col-lg-2">
                <label class="form-label">Stock Status</label>
                <select name="stock_status" class="form-select">
                    <option value="">All</option>
                    <option value="reorder_needed" @selected(request('stock_status') === 'reorder_needed')>Reorder Needed</option>
                    <option value="low" @selected(request('stock_status') === 'low')>Low Stock</option>
                    <option value="out" @selected(request('stock_status') === 'out')>Out of Stock</option>
                    <option value="no_target" @selected(request('stock_status') === 'no_target')>No Target</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">All</option>
                    <option value="none" @selected(request('supplier_id') === 'none')>Supplier Not Assigned</option>
                    @foreach ($options['suppliers'] as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Store / Location</label>
                <select name="stock_location_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($options['locations'] as $location)
                        <option value="{{ $location->id }}" @selected((string) request('stock_location_id') === (string) $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Sort</label>
                <select name="sort" class="form-select">
                    <option value="critical" @selected(request('sort', 'critical') === 'critical')>Most Critical</option>
                    <option value="lowest_sellable" @selected(request('sort') === 'lowest_sellable')>Lowest Sellable Stock</option>
                    <option value="highest_recommended" @selected(request('sort') === 'highest_recommended')>Highest Suggested Purchase</option>
                    <option value="product_name" @selected(request('sort') === 'product_name')>Product Name</option>
                    <option value="sku" @selected(request('sort') === 'sku')>SKU</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($options['categories'] as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($options['brands'] as $brand)
                        <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>SKU</th>
                    <th>Product / Variant</th>
                    <th>Store</th>
                    <th>Physical</th>
                    <th>Reserved</th>
                    <th>Sellable</th>
                    <th>Reorder</th>
                    <th>Target</th>
                    <th>Suggested</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventories as $inventory)
                    @php
                        $statusClass = match ($inventory->stock_status) {
                            'OUT_OF_STOCK' => 'text-bg-danger',
                            'LOW_STOCK' => 'text-bg-warning',
                            default => 'text-bg-success',
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $inventory->productVariant?->sku }}</td>
                        <td>
                            <div>{{ $inventory->productVariant?->product?->name }}</div>
                            <div class="small text-muted">{{ $inventory->productVariant?->variant_name ?: 'Default' }}</div>
                        </td>
                        <td>{{ $inventory->stockLocation?->name }}</td>
                        <td>{{ number_format((float) $inventory->quantity_on_hand, 3) }}</td>
                        <td>{{ number_format((float) $inventory->reserved_quantity, 3) }}</td>
                        <td class="fw-semibold">{{ number_format((float) $inventory->available_quantity, 3) }}</td>
                        <td>{{ $inventory->reorder_level === null ? 'None' : number_format((float) $inventory->reorder_level, 3) }}</td>
                        <td>{{ $inventory->target_stock_level === null ? 'Not configured' : number_format((float) $inventory->target_stock_level, 3) }}</td>
                        <td>{{ $inventory->recommended_purchase_quantity === null ? 'Not configured' : number_format((float) $inventory->recommended_purchase_quantity, 3) }}</td>
                        <td>{{ $inventory->last_supplier?->name ?? 'Supplier Not Assigned' }}</td>
                        <td><span class="badge {{ $statusClass }}">{{ str($inventory->stock_status)->replace('_', ' ')->headline() }}</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.inventories.edit', $inventory) }}" class="btn btn-outline-secondary">Settings</a>
                                <form method="POST" action="{{ route('admin.inventory.replenishment.purchase', $inventory) }}">
                                    @csrf
                                    <button class="btn btn-outline-success" @disabled($inventory->recommended_purchase_quantity === null || $inventory->recommended_purchase_quantity <= 0)>Create Purchase</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center text-muted py-5">No replenishment records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white">{{ $inventories->links() }}</div>
</div>
@endsection
