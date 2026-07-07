@php
    $variant = $product->defaultVariant;
    $image = $product->primaryImage?->path ?? $variant?->primaryImage?->path;
    $sellingPrice = $variant?->selling_price;
    $mrp = $variant?->mrp;
    $discountPercent = ($mrp && $sellingPrice && $mrp > $sellingPrice) ? round((($mrp - $sellingPrice) / $mrp) * 100) : null;
@endphp

<article class="gk-offer-card">
    @if ($discountPercent)
        <span class="gk-offer-discount">{{ $discountPercent }}%<br>OFF</span>
    @endif

    <div class="gk-offer-image">
        @if ($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}" alt="{{ $product->name }}">
        @else
            <i class="fa-solid fa-basket-shopping"></i>
        @endif
    </div>

    <div class="gk-offer-content">
        <span class="gk-offer-tag">Deal</span>
        <h3>{{ $product->name }}</h3>
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
                <button type="submit">Add to Cart</button>
            </form>
        @endif
    </div>
</article>
