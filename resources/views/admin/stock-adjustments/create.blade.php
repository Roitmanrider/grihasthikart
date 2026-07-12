@extends('layouts.admin')

@section('title', 'New Stock Adjustment')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">New Stock Adjustment</h1>
        <div class="text-muted">Increase, decrease, or set stock for one product variant.</div>
    </div>
    <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.stock-adjustments.store') }}" class="card border-0 shadow-sm">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-label" for="product_variant_id">Variant</label>
                <select id="product_variant_id" name="product_variant_id" class="form-select" required>
                    <option value="">Select variant</option>
                    @foreach ($options['variants'] as $variant)
                        @php($currentStock = $variant->inventories->sum(fn ($inventory) => (float) $inventory->quantity_on_hand))
                        <option value="{{ $variant->id }}" @selected((string) old('product_variant_id') === (string) $variant->id)>
                            {{ $variant->product?->name }} -- {{ $variant->variant_name }} -- {{ $variant->sku }} -- Current Stock: {{ number_format($currentStock, 3) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="adjustment_type">Adjustment Type</label>
                <select id="adjustment_type" name="adjustment_type" class="form-select" required>
                    @foreach (['increase' => 'Increase', 'decrease' => 'Decrease', 'set' => 'Set counted quantity'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('adjustment_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="quantity">Quantity</label>
                <input id="quantity" type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity') }}" class="form-control" required>
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="reason">Reason</label>
                <select id="reason" name="reason" class="form-select" required>
                    @foreach ($options['reasons'] as $reason)
                        <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ str($reason)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="reference_number">Reference Number</label>
                <input id="reference_number" type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control">
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="adjustment_date">Adjustment Date</label>
                <input id="adjustment_date" type="date" name="adjustment_date" value="{{ old('adjustment_date', now()->toDateString()) }}" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <a href="{{ route('admin.stock-adjustments.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-success">Save Adjustment</button>
    </div>
</form>
@endsection
