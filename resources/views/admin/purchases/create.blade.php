@extends('layouts.admin')

@section('title', 'New Purchase')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">New Purchase</h1>
        <div class="text-muted">Record stock inward against purchased product variants.</div>
    </div>
    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.purchases.store') }}">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Purchase Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="purchase_date">Purchase Date</label>
                    <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="bill_number">Bill Number</label>
                    <input id="bill_number" type="text" name="bill_number" value="{{ old('bill_number') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="">Not recorded</option>
                        @foreach ($options['suppliers'] as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Items</div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 320px;">Variant</th>
                        <th>Qty</th>
                        <th>Purchase Price</th>
                        <th>GST %</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($index = 0; $index < 8; $index++)
                        <tr>
                            <td>
                                <select name="items[{{ $index }}][product_variant_id]" class="form-select">
                                    <option value="">Select variant</option>
                                    @foreach ($options['variants'] as $variant)
                                        @php
                                            $currentStock = $variant->inventories->sum(fn ($inventory) => $inventory->available_quantity);
                                        @endphp
                                        <option value="{{ $variant->id }}" @selected((string) old("items.$index.product_variant_id") === (string) $variant->id)>
                                            {{ $variant->product?->name }} / {{ $variant->variant_name }} / {{ $variant->sku }} / Stock: {{ number_format($currentStock, 3) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.001" min="0" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity") }}" class="form-control"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][purchase_price]" value="{{ old("items.$index.purchase_price") }}" class="form-control"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][gst_rate]" value="{{ old("items.$index.gst_rate", 0) }}" class="form-control"></td>
                            <td><input type="text" name="items[{{ $index }}][batch_number]" value="{{ old("items.$index.batch_number") }}" class="form-control"></td>
                            <td><input type="date" name="items[{{ $index }}][expiry_date]" value="{{ old("items.$index.expiry_date") }}" class="form-control"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-success">Post Purchase</button>
        </div>
    </div>
</form>
@endsection
