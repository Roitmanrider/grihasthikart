@extends('layouts.admin')

@section('title', 'Record Stock Verification')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Record Stock Verification</h1>
        <div class="text-muted">Enter a physical counted stock quantity for one variant.</div>
    </div>
    <a href="{{ route('admin.stock-verifications.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.stock-verifications.store') }}" class="card border-0 shadow-sm">
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
                <label class="form-label" for="counted_stock">Counted Stock</label>
                <input id="counted_stock" type="number" step="0.001" min="0" name="counted_stock" value="{{ old('counted_stock') }}" class="form-control" required>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="verification_date">Verification Date</label>
                <input id="verification_date" type="date" name="verification_date" value="{{ old('verification_date', now()->toDateString()) }}" class="form-control" required>
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="reference_number">Reference Number</label>
                <input id="reference_number" type="text" name="reference_number" value="{{ old('reference_number') }}" class="form-control">
            </div>
            <div class="col-lg-8">
                <label class="form-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <a href="{{ route('admin.stock-verifications.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-success">Record Verification</button>
    </div>
</form>
@endsection
