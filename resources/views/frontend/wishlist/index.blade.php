@extends('layouts.frontend')

@section('title', 'Wishlist - GrihasthiKart')
@section('description', 'View your saved GrihasthiKart grocery items.')

@section('content')
    <section class="py-5 gk-wishlist-page">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Wishlist</h1>
                    <p class="text-muted mb-0">Saved grocery items ready whenever you are.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-success">Continue Shopping</a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @if ($wishlistItems->isNotEmpty())
                <div class="row g-4">
                    @foreach ($wishlistItems as $item)
                        @php
                            $variant = $item->productVariant;
                            $product = $item->product ?? $variant?->product;
                            $imageUrl = app(\App\Services\MediaResolver::class)->productImageUrl($product, $variant);
                        @endphp

                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="row g-0 h-100">
                                    <div class="col-4">
                                        @if ($imageUrl)
                                            <img src="{{ $imageUrl }}"
                                                 class="img-fluid rounded-start object-fit-cover h-100"
                                                 alt="{{ $product?->name ?? 'Wishlist item' }}">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center text-success h-100" style="min-height: 150px;">
                                                <i class="fa-solid fa-basket-shopping fa-2x"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-8">
                                        <div class="card-body h-100 d-flex flex-column">
                                            <div class="small text-muted mb-1">{{ $product?->brand?->name ?? 'GrihasthiKart' }}</div>
                                            <h2 class="h6 mb-1">
                                                @if ($product)
                                                    <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                                                        {{ $product->name }}
                                                    </a>
                                                @else
                                                    Wishlist item
                                                @endif
                                            </h2>
                                            <div class="small text-muted mb-2">{{ $variant?->variant_name }}</div>

                                            <div class="mb-3">
                                                @if ($variant)
                                                    @if ($variant->mrp > $variant->selling_price)
                                                        <span class="small text-muted text-decoration-line-through">Rs. {{ number_format((float) $variant->mrp, 0) }}</span>
                                                    @endif
                                                    <span class="fw-bold text-success">Rs. {{ number_format((float) $variant->selling_price, 0) }}</span>
                                                @else
                                                    <span class="small text-muted">Unavailable</span>
                                                @endif
                                            </div>

                                            <div class="mt-auto d-flex gap-2">
                                                @if ($variant && $variant->status)
                                                    <form method="POST" action="{{ route('wishlist.items.move-to-cart', $item) }}" class="flex-grow-1">
                                                        @csrf
                                                        <button class="btn btn-success btn-sm w-100" type="submit">Move to Cart</button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('wishlist.items.destroy', $item) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm" type="submit" aria-label="Remove from wishlist">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $wishlistItems->links() }}
                </div>
            @else
                <div class="alert alert-light border">
                    Your wishlist is empty.
                    <a href="{{ route('products.index') }}" class="alert-link">Browse products</a>
                </div>
            @endif
        </div>
    </section>
@endsection
