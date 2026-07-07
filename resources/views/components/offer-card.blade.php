@php
    $variant = $dailyOffer->productVariant;
    $product = $variant?->product;
    $imageUrl = app(\App\Services\MediaResolver::class)->productImageUrl($product, $variant);
    $sellingPrice = $dailyOffer->offer_price;
    $mrp = $variant?->mrp;
    $badge = $dailyOffer->discountBadge();
@endphp

<article class="gk-offer-card">
    @if ($badge)
        <span class="gk-offer-discount">{{ str_replace(' ', "\n", $badge) }}</span>
    @endif

    <div class="gk-offer-image">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product?->name }}">
        @else
            <i class="fa-solid fa-basket-shopping"></i>
        @endif
    </div>

    <div class="gk-offer-content">
        <span class="gk-offer-tag">{{ $dailyOffer->badge_text ?: 'Deal' }}</span>
        <h3>{{ $dailyOffer->display_title }}</h3>
        <div class="gk-offer-variant">{{ $variant?->variant_name }}</div>
        <div class="gk-offer-prices">
            @if ($mrp && $mrp > $sellingPrice)
                <span>Rs. {{ number_format((float) $mrp, 0) }}</span>
            @endif
            @if ($sellingPrice)
                <strong>Rs. {{ number_format((float) $sellingPrice, 0) }}</strong>
            @else
                <strong>Price soon</strong>
            @endif
        </div>
        @if ($variant && $variant->status)
            <form method="POST" action="{{ route('cart.items.store') }}">
                @csrf
                <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                <input type="hidden" name="quantity" value="1">
                @if ($dailyOffer->max_quantity_per_order)
                    <input type="hidden" name="max_quantity_hint" value="{{ $dailyOffer->max_quantity_per_order }}">
                @endif
                <button type="submit">Add to Cart</button>
            </form>
        @endif
    </div>
</article>
