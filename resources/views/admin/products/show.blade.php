@extends('layouts.admin')

@section('title','Product Details')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $product->name }}</h1>
        <div class="text-muted">{{ $product->slug }}</div>
    </div>

    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-success">Edit Product</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Catalog Information</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Brand</dt>
                    <dd class="col-sm-8">{{ $product->brand?->name ?? 'None' }}</dd>
                    <dt class="col-sm-4">Categories</dt>
                    <dd class="col-sm-8">
                        @foreach ($product->categories as $category)
                            <span class="badge text-bg-light border">{{ $category->name }}{{ $category->pivot->is_primary ? ' *' : '' }}</span>
                        @endforeach
                    </dd>
                    <dt class="col-sm-4">Barcode</dt>
                    <dd class="col-sm-8">{{ $product->barcode ?? 'None' }}</dd>
                    <dt class="col-sm-4">HSN / GST</dt>
                    <dd class="col-sm-8">{{ $product->hsn_code ?? 'None' }} / {{ $product->gst_rate ?? 'None' }}</dd>
                    <dt class="col-sm-4">Manufacturer</dt>
                    <dd class="col-sm-8">{{ $product->manufacturer ?? 'None' }}</dd>
                    <dt class="col-sm-4">Country</dt>
                    <dd class="col-sm-8">{{ $product->country_of_origin ?? 'None' }}</dd>
                    <dt class="col-sm-4">Shelf Life</dt>
                    <dd class="col-sm-8">{{ $product->shelf_life ?? 'None' }}</dd>
                    <dt class="col-sm-4">Order Limits</dt>
                    <dd class="col-sm-8">{{ $product->minimum_order_quantity }} / {{ $product->maximum_order_quantity ?? 'No maximum' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Descriptions</div>
            <div class="card-body">
                <p class="mb-3">{{ $product->short_description }}</p>
                <div>{!! nl2br(e($product->description)) !!}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Status</div>
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge {{ $product->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $product->status ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">SEO</div>
            <div class="card-body">
                <div class="fw-semibold">{{ $product->meta_title ?? 'No meta title' }}</div>
                <div class="text-muted small mt-2">{{ $product->meta_description ?? 'No meta description' }}</div>
                <div class="small mt-2">{{ $product->meta_keywords ?? 'No meta keywords' }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
