@php
    $variant = $product->defaultVariant;
    $image = $product->primaryImage?->path ?? $variant?->primaryImage?->path;
    $sellingPrice = $variant?->selling_price;
    $mrp = $variant?->mrp;
    $discountPercent = ($mrp && $sellingPrice && $mrp > $sellingPrice) ? round((($mrp - $sellingPrice) / $mrp) * 100) : null;
@endphp

<article class="card h-100 border-0 shadow-sm overflow-hidden">

    <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none">

        @if ($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                 class="card-img-top object-fit-cover"
                 style="height: 190px; background: #f8f9fa;"
                 alt="{{ $product->primaryImage?->alt_text ?? $product->name }}">
        @else
            <div class="bg-light d-flex align-items-center justify-content-center text-success" style="height: 190px;">
                <i class="fa-solid fa-basket-shopping fa-2x"></i>
            </div>
        @endif

    </a>

    <div class="card-body d-flex flex-column gap-2">

        <div class="d-flex flex-wrap gap-1">
            @if ($product->is_featured)
                <span class="badge text-bg-success">Featured</span>
            @endif

            @if ($product->is_trending)
                <span class="badge text-bg-warning">Trending</span>
            @endif

            @if ($product->is_new_arrival)
                <span class="badge text-bg-info">New</span>
            @endif
        </div>

        <div class="text-muted small">{{ $product->brand?->name ?? 'GrihasthiKart' }}</div>

        <h3 class="h6 mb-0">
            <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                {{ $product->name }}
            </a>
        </h3>

        <div class="small text-muted">{{ $variant?->variant_name }}</div>

        <div class="mt-auto">
            @if ($sellingPrice)
                <div class="fw-bold text-success">Rs. {{ number_format((float) $sellingPrice, 2) }}</div>

                @if ($mrp && $mrp > $sellingPrice)
                    <div class="small text-muted">
                        <span class="text-decoration-line-through">Rs. {{ number_format((float) $mrp, 2) }}</span>
                        @if ($discountPercent)
                            <span class="text-success ms-1">{{ $discountPercent }}% off</span>
                        @endif
                    </div>
                @endif
            @else
                <div class="text-muted small">Price coming soon</div>
            @endif
        </div>

        <div class="d-grid gap-2 mt-2">
            <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline-success btn-sm">
                View product
            </a>

            @if ($variant && $variant->status)
                <form method="POST" action="{{ route('cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button class="btn btn-success btn-sm w-100" type="submit">Add to Cart</button>
                </form>
            @else
                <button class="btn btn-secondary btn-sm" type="button" disabled>Unavailable</button>
            @endif
        </div>

    </div>

</article>
