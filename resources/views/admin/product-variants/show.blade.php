@extends('layouts.admin')

@section('title','Product Variant Details')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $productVariant->variant_name }}</h1>
        <div class="text-muted">{{ $product->name }} / {{ $productVariant->sku }}</div>
    </div>

    <a href="{{ route('admin.products.variants.edit', [$product, $productVariant]) }}" class="btn btn-success">Edit Variant</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Variant Information</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">SKU</dt>
                    <dd class="col-sm-8">{{ $productVariant->sku }}</dd>
                    <dt class="col-sm-4">Barcode</dt>
                    <dd class="col-sm-8">{{ $productVariant->barcode ?? 'None' }}</dd>
                    <dt class="col-sm-4">MRP</dt>
                    <dd class="col-sm-8">{{ $productVariant->mrp }}</dd>
                    <dt class="col-sm-4">Selling Price</dt>
                    <dd class="col-sm-8">{{ $productVariant->selling_price }}</dd>
                    <dt class="col-sm-4">Purchase Price</dt>
                    <dd class="col-sm-8">{{ $productVariant->purchase_price ?? 'None' }}</dd>
                    <dt class="col-sm-4">Weight / Unit</dt>
                    <dd class="col-sm-8">{{ $productVariant->weight ?? 'None' }} {{ $productVariant->unit }}</dd>
                    <dt class="col-sm-4">Signature</dt>
                    <dd class="col-sm-8">{{ $productVariant->attribute_signature }}</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Attributes</div>
            <div class="card-body">
                @forelse ($productVariant->attributeValues as $value)
                    <span class="badge text-bg-light border">{{ $value->attribute?->name }}: {{ $value->value }}</span>
                @empty
                    <span class="text-muted">Default</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Product</div>
            <div class="card-body">
                <div class="fw-semibold">{{ $product->name }}</div>
                <div class="text-muted small">{{ $product->slug }}</div>
                <div class="mt-3">{{ $product->brand?->name ?? 'No brand' }}</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Status</div>
            <div class="card-body">
                <div><span class="badge {{ $productVariant->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $productVariant->status ? 'Active' : 'Inactive' }}</span></div>
                <div class="mt-2">Default: {{ $productVariant->is_default ? 'Yes' : 'No' }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
