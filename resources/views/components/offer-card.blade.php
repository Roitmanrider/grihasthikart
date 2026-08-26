@php
    $variant = $dailyOffer->productVariant;
    $product = $variant?->product;
    $imageUrl = app(\App\Services\MediaResolver::class)->productImageUrl($product, $variant);
    $sellingPrice = $dailyOffer->offer_price;
    $normalPrice = $variant?->mrp;
    $discountBadge = $dailyOffer->discountBadge();
    $availableOfferStock = (float) $dailyOffer->availableOfferQuantity();
    $customBadge = $dailyOffer->badge_text ?: 'Daily Offer';
@endphp

<article class="gk-offer-card">
    <div class="gk-offer-card-badges">
        <span class="gk-offer-tag" title="{{ $customBadge }}">{{ $customBadge }}</span>
        @if ($discountBadge)
            <span class="gk-offer-discount">{{ $discountBadge }}</span>
        @endif
    </div>

    <div class="gk-offer-image">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $product?->name }}">
        @else
            <i class="fa-solid fa-basket-shopping"></i>
        @endif
    </div>

    <div class="gk-offer-content">
        @if ($product?->brand)
            <div class="gk-offer-brand">{{ $product->brand->name }}</div>
        @endif
        <h3>{{ $dailyOffer->display_title }}</h3>
        <div class="gk-offer-variant">{{ $variant?->variant_name }}@if($variant?->weight) / {{ $variant->weight }}@endif</div>
        <div class="gk-offer-prices">
            @if ($normalPrice && $normalPrice > $sellingPrice)
                <span><small>Unit MRP</small> Rs. {{ number_format((float) $normalPrice, 0) }}</span>
            @endif
            @if ($sellingPrice)
                <strong><small>GK Offer</small> Rs. {{ number_format((float) $sellingPrice, 0) }}</strong>
            @else
                <strong>Price soon</strong>
            @endif
        </div>
        <div class="gk-offer-meta">{{ $dailyOffer->remainingTimeLabel() }}</div>
        @if ($dailyOffer->max_quantity_per_order)
            <div class="gk-offer-meta">Max {{ $dailyOffer->max_quantity_per_order }} per order</div>
        @endif
        <div class="gk-offer-meta">
            @if ($dailyOffer->lifecycleState() === 'Expired')
                Offer ended
            @elseif ($availableOfferStock <= 0)
                Sold out at this price
            @else
                Only {{ number_format($availableOfferStock, 0) }} left at this price
            @endif
        </div>
        @if ($variant && $variant->status && $availableOfferStock > 0 && $dailyOffer->lifecycleState() === 'Active')
            <form method="POST" action="{{ route('cart.items.store') }}">
                @csrf
                <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                <input type="hidden" name="daily_offer_id" value="{{ $dailyOffer->id }}">
                <input type="hidden" name="quantity" value="1">
                @if ($dailyOffer->max_quantity_per_order)
                    <input type="hidden" name="max_quantity_hint" value="{{ $dailyOffer->max_quantity_per_order }}">
                @endif
                <button type="submit">Add to Cart</button>
            </form>
        @else
            <button type="button" disabled>{{ $dailyOffer->lifecycleState() === 'Expired' ? 'Offer Ended' : 'Sold Out' }}</button>
        @endif
    </div>
</article>
