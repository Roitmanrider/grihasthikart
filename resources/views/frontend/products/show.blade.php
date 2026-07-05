@extends('layouts.frontend')

@section('title', $product->meta_title ?: $product->name.' - GrihasthiKart')
@section('description', $product->meta_description ?: $product->short_description)

@php
    $defaultVariant = $product->defaultVariant;
    $galleryImages = $product->images->merge($product->variants->flatMap->images)->unique('id')->values();
    $primaryImage = $product->primaryImage?->path ?? $defaultVariant?->primaryImage?->path;
@endphp

@section('content')
    <section class="py-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="border rounded-3 bg-light overflow-hidden mb-3">
                        @if ($primaryImage)
                            <img id="variantImage"
                                 src="{{ \Illuminate\Support\Facades\Storage::url($primaryImage) }}"
                                 class="w-100 object-fit-cover"
                                 style="height: 430px;"
                                 alt="{{ $product->name }}">
                        @else
                            <div id="variantImagePlaceholder" class="ratio ratio-4x3 d-flex align-items-center justify-content-center text-success">
                                <i class="fa-solid fa-basket-shopping fa-4x"></i>
                            </div>
                        @endif
                    </div>

                    @if ($galleryImages->isNotEmpty())
                        <div class="row g-2">
                            @foreach ($galleryImages as $image)
                                <div class="col-3">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($image->path) }}"
                                         class="img-thumbnail object-fit-cover w-100"
                                         style="height: 90px;"
                                         alt="{{ $image->alt_text ?: $product->name }}">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if ($product->is_featured)
                            <span class="badge text-bg-success">Featured</span>
                        @endif
                        @if ($product->is_trending)
                            <span class="badge text-bg-warning">Trending</span>
                        @endif
                        @if ($product->is_popular)
                            <span class="badge text-bg-primary">Popular</span>
                        @endif
                        @if ($product->is_new_arrival)
                            <span class="badge text-bg-info">New arrival</span>
                        @endif
                    </div>

                    <h1 class="h2">{{ $product->name }}</h1>
                    <p class="text-muted">{{ $product->brand?->name }}</p>

                    @if ($product->short_description)
                        <p class="lead">{{ $product->short_description }}</p>
                    @endif

                    <div class="border rounded-3 p-4 mb-4">
                        <label for="variantSelector" class="form-label fw-semibold">Choose variant</label>
                        <select id="variantSelector" class="form-select mb-3">
                            @foreach ($product->variants as $variant)
                                @php
                                    $variantImage = $variant->primaryImage?->path ?? $product->primaryImage?->path;
                                @endphp
                                <option value="{{ $variant->id }}"
                                        data-price="{{ number_format((float) $variant->selling_price, 2) }}"
                                        data-mrp="{{ number_format((float) $variant->mrp, 2) }}"
                                        data-sku="{{ $variant->sku }}"
                                        data-barcode="{{ $variant->barcode }}"
                                        data-weight="{{ $variant->weight }} {{ $variant->unit }}"
                                        data-image="{{ $variantImage ? \Illuminate\Support\Facades\Storage::url($variantImage) : '' }}"
                                        @selected($defaultVariant?->id === $variant->id)>
                                    {{ $variant->variant_name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="h3 text-success mb-1">Rs. <span id="variantPrice">{{ number_format((float) $defaultVariant?->selling_price, 2) }}</span></div>
                        <div class="text-muted small mb-3">MRP Rs. <span id="variantMrp">{{ number_format((float) $defaultVariant?->mrp, 2) }}</span></div>

                        <dl class="row small mb-0">
                            <dt class="col-sm-4">SKU</dt>
                            <dd class="col-sm-8" id="variantSku">{{ $defaultVariant?->sku }}</dd>
                            <dt class="col-sm-4">Barcode</dt>
                            <dd class="col-sm-8" id="variantBarcode">{{ $defaultVariant?->barcode ?: 'Not available' }}</dd>
                            <dt class="col-sm-4">Weight</dt>
                            <dd class="col-sm-8" id="variantWeight">{{ trim($defaultVariant?->weight.' '.$defaultVariant?->unit) }}</dd>
                        </dl>
                    </div>

                    <button class="btn btn-secondary btn-lg" type="button" disabled>Cart coming soon</button>

                    <hr class="my-4">

                    <dl class="row">
                        <dt class="col-sm-4">HSN</dt>
                        <dd class="col-sm-8">{{ $product->hsn_code ?: 'Not available' }}</dd>
                        <dt class="col-sm-4">GST</dt>
                        <dd class="col-sm-8">{{ $product->gst_rate !== null ? $product->gst_rate.'%' : 'Not available' }}</dd>
                        <dt class="col-sm-4">Manufacturer</dt>
                        <dd class="col-sm-8">{{ $product->manufacturer ?: 'Not available' }}</dd>
                        <dt class="col-sm-4">Country</dt>
                        <dd class="col-sm-8">{{ $product->country_of_origin ?: 'Not available' }}</dd>
                        <dt class="col-sm-4">Shelf life</dt>
                        <dd class="col-sm-8">{{ $product->shelf_life ?: 'Not available' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($product->description)
                <div class="mt-5">
                    <h2 class="h4">Product Details</h2>
                    <div class="text-muted">{!! nl2br(e($product->description)) !!}</div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selector = document.getElementById('variantSelector');

            if (!selector) {
                return;
            }

            selector.addEventListener('change', () => {
                const option = selector.selectedOptions[0];
                const image = document.getElementById('variantImage');

                document.getElementById('variantPrice').textContent = option.dataset.price || '';
                document.getElementById('variantMrp').textContent = option.dataset.mrp || '';
                document.getElementById('variantSku').textContent = option.dataset.sku || '';
                document.getElementById('variantBarcode').textContent = option.dataset.barcode || 'Not available';
                document.getElementById('variantWeight').textContent = option.dataset.weight || 'Not available';

                if (image && option.dataset.image) {
                    image.setAttribute('src', option.dataset.image);
                }
            });
        });
    </script>
@endpush
