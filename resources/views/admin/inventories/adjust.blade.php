@extends('layouts.admin')

@section('title','Adjust Inventory')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Adjust Inventory</h1>
        <div class="text-muted">{{ $inventory->productVariant?->product?->name }} / {{ $inventory->productVariant?->sku }}</div>
    </div>

    <a href="{{ route('admin.inventories.show', $inventory) }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Current Balance</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">On Hand</dt>
                    <dd class="col-sm-7">{{ number_format((float) $inventory->quantity_on_hand, 3) }}</dd>
                    <dt class="col-sm-5">Reserved</dt>
                    <dd class="col-sm-7">{{ number_format((float) $inventory->reserved_quantity, 3) }}</dd>
                    <dt class="col-sm-5">Damaged</dt>
                    <dd class="col-sm-7">{{ number_format((float) $inventory->damaged_quantity, 3) }}</dd>
                    <dt class="col-sm-5">Available</dt>
                    <dd class="col-sm-7 fw-semibold">{{ number_format($inventory->available_quantity, 3) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">New Adjustment</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.inventories.adjust.store', $inventory) }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Movement Type</label>
                        <select name="movement_type" class="form-select" required>
                            @foreach (\App\Models\InventoryMovement::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('movement_type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity') }}" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Apply Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
