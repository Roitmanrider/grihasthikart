@php
    $variant = $product->defaultVariant;
    $image = $product->primaryImage?->path ?? $variant?->primaryImage?->path;
    $sellingPrice = $variant?->selling_price;
    $mrp = $variant?->mrp;
    $discountPercent = ($mrp && $sellingPrice && $mrp > $sellingPrice) ? round((($mrp - $sellingPrice) / $mrp) * 100) : null;
@endphp

<article class="gk-product-card">

    @if ($discountPercent)
        <div class="gk-discount-badge">{{ $discountPercent }}%<br>OFF</div>
    @endif

    <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none">

        @if ($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                 class="gk-product-image"
                 alt="{{ $product->primaryImage?->alt_text ?? $product->name }}">
        @else
            <div class="gk-product-fallback">
                <i class="fa-solid fa-basket-shopping"></i>
            </div>
        @endif

    </a>

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
            <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                {{ $product->name }}
            </a>
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
                <form method="POST" action="{{ route('cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn btn-sm w-100" type="submit">Add to Cart</button>
                </form>
            @else
                <button class="btn btn-sm w-100" type="button" disabled>Unavailable</button>
            @endif
        </div>

    </div>

</article>
