@php
    $variant = $product->defaultVariant;
    $mediaResolver = app(\App\Services\MediaResolver::class);
    $imageUrl = $mediaResolver->productImageUrl($product, $variant);
    $sellingPrice = $variant?->selling_price;
    $mrp = $variant?->mrp;
    $discountPercent = ($mrp && $sellingPrice && $mrp > $sellingPrice) ? round((($mrp - $sellingPrice) / $mrp) * 100) : null;
    $currentCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $wishlistedVariantIds = app(\App\Domains\Wishlist\Services\WishlistService::class)->activeVariantIdsForCustomer($currentCustomer);
    $isWishlisted = $variant && in_array((int) $variant->id, $wishlistedVariantIds, true);
    $quickViewId = 'quickViewProduct'.$product->id;
@endphp

<article class="gk-product-card">

    @if ($discountPercent)
        <div class="gk-discount-badge">{{ $discountPercent }}%<br>OFF</div>
    @endif

    <button type="button" class="gk-product-quick-trigger" data-bs-toggle="modal" data-bs-target="#{{ $quickViewId }}" aria-label="Quick view {{ $product->name }}">

        @if ($imageUrl)
            <img src="{{ $imageUrl }}"
                 class="gk-product-image"
                 alt="{{ $product->primaryImage?->alt_text ?? $product->name }}">
        @else
            <div class="gk-product-fallback">
                <i class="fa-solid fa-basket-shopping"></i>
            </div>
        @endif

    </button>

    <div class="gk-product-body">

        <div class="gk-product-badges">
            @if ($product->is_featured)
                <span>Deal</span>
            @endif

            @if ($product->is_trending)
                <span>Trending</span>
            @endif

            @if ($product->is_new_arrival)
                <span>New</span>
            @endif
        </div>

        <div class="gk-product-brand">{{ $product->brand?->name ?? 'GrihasthiKart' }}</div>

        <h3>
            <button type="button" class="gk-product-title-button" data-bs-toggle="modal" data-bs-target="#{{ $quickViewId }}">
                {{ $product->name }}
            </button>
        </h3>

        <div class="gk-product-variant">{{ $variant?->variant_name }}</div>

        <div class="gk-product-price">
            @if ($sellingPrice)
                @if ($mrp && $mrp > $sellingPrice)
                    <span class="gk-mrp">Rs. {{ number_format((float) $mrp, 0) }}</span>
                @endif

                <span class="gk-selling-price">Rs. {{ number_format((float) $sellingPrice, 0) }}</span>
                <span class="visually-hidden">Rs. {{ number_format((float) $sellingPrice, 2) }}</span>
            @else
                <span class="text-muted small">Price coming soon</span>
            @endif
        </div>

        <div class="gk-product-actions">
            @if ($variant && $variant->status)
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('cart.items.store') }}" class="flex-grow-1">
                        @csrf
                        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button class="btn btn-sm w-100" type="submit">Add to Cart</button>
                    </form>

                    <form method="POST" action="{{ route('wishlist.items.store') }}">
                        @csrf
                        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                        <button class="btn btn-sm gk-wishlist-button {{ $isWishlisted ? 'is-active' : '' }}" type="submit" aria-label="{{ $isWishlisted ? 'Saved in wishlist' : 'Add '.$product->name.' to wishlist' }}">
                            <i class="{{ $isWishlisted ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
                        </button>
                    </form>
                </div>
            @else
                <button class="btn btn-sm w-100" type="button" disabled>Unavailable</button>
            @endif
        </div>

    </div>

</article>

<div class="modal fade" id="{{ $quickViewId }}" tabindex="-1" aria-labelledby="{{ $quickViewId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h2 class="modal-title h5" id="{{ $quickViewId }}Label">{{ $product->name }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-sm-5">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" class="img-fluid rounded" alt="{{ $product->name }}">
                        @else
                            <div class="gk-product-fallback rounded h-100">
                                <i class="fa-solid fa-basket-shopping"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-7">
                        <div class="small text-muted mb-1">{{ $product->brand?->name ?? 'GrihasthiKart' }}</div>
                        <div class="fw-semibold mb-2">{{ $variant?->variant_name }}</div>
                        <div class="mb-3">
                            @if ($mrp && $mrp > $sellingPrice)
                                <span class="text-muted text-decoration-line-through me-2">Rs. {{ number_format((float) $mrp, 0) }}</span>
                            @endif
                            @if ($sellingPrice)
                                <span class="fw-bold text-success">Rs. {{ number_format((float) $sellingPrice, 0) }}</span>
                            @endif
                        </div>
                        @if ($product->short_description)
                            <p class="small text-muted">{{ $product->short_description }}</p>
                        @endif
                        @if ($variant && $variant->status)
                            <form method="POST" action="{{ route('cart.items.store') }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                                <input type="number" name="quantity" value="1" min="1" step="1" class="form-control form-control-sm" style="max-width: 88px;">
                                <button class="btn btn-success btn-sm" type="submit">Add to Cart</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
