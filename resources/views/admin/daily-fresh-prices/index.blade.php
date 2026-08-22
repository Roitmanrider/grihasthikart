@extends('layouts.admin')

@section('title', 'Daily Fresh Prices')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Daily Fresh Prices</h1>
        <div class="text-muted">Store-specific price overrides with compact history.</div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.daily-fresh-prices.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Store</label>
                <select name="stock_location_id" class="form-select">
                    @foreach ($stores as $optionStore)
                        <option value="{{ $optionStore->id }}" @selected($store?->id === $optionStore->id)>{{ $optionStore->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-success w-100">View</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.daily-fresh-prices.store') }}" class="card border-0 shadow-sm mb-4">
    @csrf
    <input type="hidden" name="stock_location_id" value="{{ $store?->id }}">
    <div class="card-body row g-3">
        <div class="col-md-5">
            <label class="form-label">Variant</label>
            <select name="product_variant_id" class="form-select" required>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}">{{ $variant->sku }} - {{ $variant->product?->name }} / {{ $variant->variant_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">MRP</label>
            <input type="number" step="0.01" min="0" name="mrp" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">Selling Price</label>
            <input type="number" step="0.01" min="0" name="selling_price" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Reason</label>
            <input name="change_reason" class="form-control" placeholder="Daily fresh update">
        </div>
        <div class="col-md-3">
            <label class="form-label">Effective From</label>
            <input type="datetime-local" name="effective_from" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Effective Until</label>
            <input type="datetime-local" name="effective_until" class="form-control">
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">Save Price</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Variant</th>
                    <th>MRP</th>
                    <th>Selling Price</th>
                    <th>Source</th>
                    <th>Effective</th>
                    <th>Changed By</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prices as $price)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $price->productVariant?->product?->name }}</div>
                            <div class="text-muted small">{{ $price->productVariant?->variant_name }} / {{ $price->productVariant?->sku }}</div>
                        </td>
                        <td>{{ number_format((float) $price->mrp, 2) }}</td>
                        <td>{{ number_format((float) $price->selling_price, 2) }}</td>
                        <td>{{ ucfirst($price->source) }}</td>
                        <td class="small text-muted">
                            {{ $price->effective_from?->format('d M H:i') ?: 'Now' }} - {{ $price->effective_until?->format('d M H:i') ?: 'Open' }}
                        </td>
                        <td>{{ $price->changedBy?->name ?: 'System' }}</td>
                        <td>{{ $price->updated_at?->format('d M Y, h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No store prices configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $prices->links() }}</div>
@endsection
