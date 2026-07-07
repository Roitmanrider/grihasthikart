@extends('layouts.admin')

@section('title','Edit Image')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Image</h1>
    <div class="text-muted">{{ isset($productVariant) ? $productVariant->variant_name : $product->name }}</div>
</div>

<form method="POST" action="{{ isset($productVariant) ? route('admin.products.variants.images.update', [$product, $productVariant, $productImage]) : route('admin.products.images.update', [$product, $productImage]) }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <img src="{{ app(\App\Services\MediaResolver::class)->url($productImage->path) }}" class="card-img-top" alt="{{ $productImage->alt_text }}" style="max-height: 360px; object-fit: cover;">
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" value="{{ old('title', $productImage->title) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alt Text</label>
                            <input type="text" name="alt_text" value="{{ old('alt_text', $productImage->alt_text) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="display_order" value="{{ old('display_order', $productImage->display_order) }}" class="form-control" min="0">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_primary" value="0">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="is_primary" @checked(old('is_primary', $productImage->is_primary))>
                                <label class="form-check-label" for="is_primary">Primary</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="hidden" name="status" value="0">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="status" @checked(old('status', $productImage->status))>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex gap-2">
                    <button class="btn btn-success">Update Image</button>
                    <a href="{{ isset($productVariant) ? route('admin.products.variants.edit', [$product, $productVariant]) : route('admin.products.edit', $product) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection
