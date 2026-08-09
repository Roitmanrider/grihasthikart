@php
    $variants = $product->variants->where('status', true)->values();
    $variant = $variants->firstWhere('id', $product->default_variant_id) ?? $variants->first();
    $variantOffers = $variants
        ->flatMap(fn ($variant) => $variant->inventories->sum('available_quantity') > 0 ? $variant->dailyOffers : collect())
        ->filter(fn ($offer) => $offer->lifecycleState() === 'Active' && $offer->availableOfferQuantity() > 0)
        ->values();
    $defaultOffer = $variantOffers->firstWhere('product_variant_id', $variant?->id);
    $offeredVariantNames = $variants
        ->whereIn('id', $variantOffers->pluck('product_variant_id')->all())
        ->pluck('variant_name')
        ->filter()
        ->values();
    $mediaResolver = app(\App\Services\MediaResolver::class);
    $imageUrl = $mediaResolver->productImageUrl($product, $variant);
    $sellingPrice = $defaultOffer?->offer_price ?? $variant?->selling_price;
    $normalPrice = $variant?->selling_price;
    $mrp = $variant?->mrp;
    $variantStock = (float) ($variant?->inventories?->sum('available_quantity') ?? 0);
    $variantPurchasable = $variant && $variantStock > 0;
    $discountPercent = $defaultOffer
        ? $defaultOffer->discountPercentage()
        : (($mrp && $sellingPrice && $mrp > $sellingPrice) ? round((($mrp - $sellingPrice) / $mrp) * 100) : null);
    $currentCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $wishlistedVariantIds = app(\App\Domains\Wishlist\Services\WishlistService::class)->activeVariantIdsForCustomer($currentCustomer);
    $isWishlisted = $variant && in_array((int) $variant->id, $wishlistedVariantIds, true);
    $quickViewId = 'quickViewProduct'.$product->id;
    $quickViewSelectorId = $quickViewId.'VariantSelector';
    $variantSelectorId = 'variantSelectorProduct'.$product->id;
@endphp

<article class="gk-product-card">

    @if ($defaultOffer)
        <div class="gk-discount-badge">Daily Offer<br>{{ $variant?->variant_name }}</div>
    @elseif ($variantOffers->isNotEmpty())
        <div class="gk-discount-badge">
            Daily Offer<br>
            {{ $offeredVariantNames->count() <= 2 ? $offeredVariantNames->implode(', ') : $offeredVariantNames->count().' Variants' }}
        </div>
    @elseif ($discountPercent)
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

            @if ($defaultOffer)
                <span>Daily Offer</span>
            @endif
        </div>

        <div class="gk-product-brand">{{ $product->brand?->name ?? 'GrihasthiKart' }}</div>

        <h3>
            <button type="button" class="gk-product-title-button" data-bs-toggle="modal" data-bs-target="#{{ $quickViewId }}">
                {{ $product->name }}
            </button>
        </h3>

        <div class="gk-product-variant">
            {{ $variant?->variant_name }}
            @if ($variants->count() > 1)
                <span class="text-muted">| {{ $variants->count() }} variants available</span>
            @endif
        </div>

        <div class="gk-product-price">
            @if ($sellingPrice)
                @if ($defaultOffer && $normalPrice > $sellingPrice)
                    <span class="gk-mrp">Rs. {{ number_format((float) $normalPrice, 0) }}</span>
                @elseif ($mrp && $mrp > $sellingPrice)
                    <span class="gk-mrp">Rs. {{ number_format((float) $mrp, 0) }}</span>
                @endif

                <span class="gk-selling-price">Rs. {{ number_format((float) $sellingPrice, 0) }}</span>
                <span class="visually-hidden">Rs. {{ number_format((float) $sellingPrice, 2) }}</span>
            @else
                <span class="text-muted small">Price coming soon</span>
            @endif
        </div>
        @if ($defaultOffer)
            <div class="small text-success mb-2">
                {{ $defaultOffer->remainingTimeLabel() }} ·
                @if ($defaultOffer->availableOfferQuantity() > 10)
                    Limited Daily Offer Stock
                @elseif ($defaultOffer->availableOfferQuantity() > 3)
                    Only {{ number_format($defaultOffer->availableOfferQuantity(), 0) }} offer units left
                @else
                    Only {{ number_format($defaultOffer->availableOfferQuantity(), 0) }} left at this price
                @endif
            </div>
        @endif
        @if ($variant && $variantStock <= 0)
            <div class="small text-danger mb-2">Out of Stock</div>
        @endif

        <div class="gk-product-actions">
            @if ($variant && $variant->status)
                <div class="d-flex gap-2">
                    @if ($variants->count() > 1)
                        <button class="btn btn-sm w-100 flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $variantSelectorId }}" aria-expanded="false" aria-controls="{{ $variantSelectorId }}">
                            Choose Variant
                        </button>
                    @else
                        <form method="POST" action="{{ route('cart.items.store') }}" class="flex-grow-1">
                            @csrf
                            <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                            @if ($defaultOffer)
                                <input type="hidden" name="daily_offer_id" value="{{ $defaultOffer->id }}">
                            @endif
                            <input type="hidden" name="quantity" value="1">
                            <button class="btn btn-sm w-100" type="submit" @disabled(! $variantPurchasable)>{{ $variantPurchasable ? 'Add to Cart' : 'Out of Stock' }}</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('wishlist.items.store') }}">
                        @csrf
                        <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                        <button class="btn btn-sm gk-wishlist-button {{ $isWishlisted ? 'is-active' : '' }}" type="submit" aria-label="{{ $isWishlisted ? 'Saved in wishlist' : 'Add '.$product->name.' to wishlist' }}">
                            <i class="{{ $isWishlisted ? 'fa-solid' : 'fa-regular' }} fa-heart"></i>
                        </button>
                    </form>
                </div>
                @if ($variants->count() > 1)
                    <div class="collapse mt-2" id="{{ $variantSelectorId }}">
                        <div class="border rounded p-2 bg-white">
                            @foreach ($variants as $selectorVariant)
                                @php
                                    $selectorOffer = $variantOffers->firstWhere('product_variant_id', $selectorVariant->id);
                                    $selectorPrice = $selectorOffer?->offer_price ?? $selectorVariant->selling_price;
                                    $selectorStock = (float) $selectorVariant->inventories->sum('available_quantity');
                                    $selectorDiscount = $selectorOffer ? $selectorOffer->discountPercentage() : null;
                                @endphp
                                <div class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom">
                                    <div class="small">
                                        <div class="fw-semibold">{{ $selectorVariant->variant_name }}</div>
                                        <div>
                                            @if ($selectorOffer)
                                                <span class="text-muted text-decoration-line-through">Rs. {{ number_format((float) $selectorVariant->selling_price, 0) }}</span>
                                            @elseif ($selectorVariant->mrp > $selectorPrice)
                                                <span class="text-muted text-decoration-line-through">Rs. {{ number_format((float) $selectorVariant->mrp, 0) }}</span>
                                            @endif
                                            <span class="fw-semibold text-success">Rs. {{ number_format((float) $selectorPrice, 0) }}</span>
                                        </div>
                                        @if ($selectorOffer)
                                            <div class="text-success">Daily Offer{{ $selectorDiscount ? ' · '.number_format($selectorDiscount, 0).'% OFF' : '' }}</div>
                                            <div class="text-muted">{{ $selectorOffer->remainingTimeLabel() }}</div>
                                        @endif
                                        @if ($selectorStock <= 0)
                                            <div class="text-danger">Out of Stock</div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('cart.items.store') }}">
                                        @csrf
                                        <input type="hidden" name="product_variant_id" value="{{ $selectorVariant->id }}">
                                        <input type="hidden" name="daily_offer_id" value="{{ $selectorOffer?->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="btn btn-sm btn-success" type="submit" @disabled($selectorStock <= 0)>Add</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
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
                        @if ($variants->count() > 1)
                            <label class="form-label small fw-semibold" for="{{ $quickViewSelectorId }}">Choose variant</label>
                            <select id="{{ $quickViewSelectorId }}" class="form-select form-select-sm gk-quick-variant-selector mb-2" data-modal-id="{{ $quickViewId }}">
                                @foreach ($variants as $optionVariant)
                                    @php
                                        $optionOffer = $variantOffers->firstWhere('product_variant_id', $optionVariant->id);
                                        $optionImage = $optionVariant->primaryImage?->path ?? $product->primaryImage?->path;
                                        $optionPrice = $optionOffer?->offer_price ?? $optionVariant->selling_price;
                                        $optionStock = $optionVariant->inventories->sum('available_quantity');
                                        $optionAvailability = $optionOffer
                                            ? ($optionOffer->availableOfferQuantity() > 10
                                                ? 'Limited Daily Offer Stock'
                                                : ($optionOffer->availableOfferQuantity() > 3
                                                    ? 'Only '.number_format($optionOffer->availableOfferQuantity(), 0).' offer units left'
                                                    : 'Only '.number_format($optionOffer->availableOfferQuantity(), 0).' left at this price'))
                                            : '';
                                    @endphp
                                    <option value="{{ $optionVariant->id }}"
                                            data-name="{{ $optionVariant->variant_name }}"
                                            data-price="{{ number_format((float) $optionPrice, 0) }}"
                                            data-normal-price="{{ number_format((float) $optionVariant->selling_price, 0) }}"
                                            data-mrp="{{ number_format((float) $optionVariant->mrp, 0) }}"
                                            data-sku="{{ $optionVariant->sku }}"
                                            data-image="{{ $optionImage ? $mediaResolver->url($optionImage) : '' }}"
                                            data-stock="{{ (float) $optionStock }}"
                                            data-max-quantity="{{ $product->maximum_order_quantity ?: '' }}"
                                            data-daily-offer-id="{{ $optionOffer?->id }}"
                                            data-sale-type="{{ $optionOffer ? 'daily_offer' : 'normal' }}"
                                            data-offer-discount="{{ $optionOffer ? number_format($optionOffer->discountPercentage(), 0) : '' }}"
                                            data-offer-countdown="{{ $optionOffer?->remainingTimeLabel() }}"
                                            data-offer-availability="{{ $optionAvailability }}"
                                            @selected($variant?->id === $optionVariant->id)>
                                        {{ $optionVariant->variant_name }}{{ $optionOffer ? ' - Daily Offer' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <div class="fw-semibold mb-2" data-quick-variant-name>{{ $variant?->variant_name }}</div>
                        <div class="mb-2">
                            @if ($defaultOffer && $normalPrice > $sellingPrice)
                                <span class="text-muted text-decoration-line-through me-2" data-quick-normal-price>Rs. {{ number_format((float) $normalPrice, 0) }}</span>
                            @elseif ($mrp && $mrp > $sellingPrice)
                                <span class="text-muted text-decoration-line-through me-2" data-quick-normal-price>Rs. {{ number_format((float) $mrp, 0) }}</span>
                            @else
                                <span class="text-muted text-decoration-line-through me-2 d-none" data-quick-normal-price></span>
                            @endif
                            @if ($sellingPrice)
                                <span class="fw-bold text-success" data-quick-price>Rs. {{ number_format((float) $sellingPrice, 0) }}</span>
                            @endif
                        </div>
                        <div class="small text-muted mb-3">
                            SKU: <span data-quick-sku>{{ $variant?->sku }}</span>
                            <span class="mx-1">|</span>
                            Stock: <span data-quick-stock>{{ number_format((float) $variant?->inventories?->sum('available_quantity'), 0) }}</span>
                            <span class="badge text-bg-success ms-1 {{ $defaultOffer ? '' : 'd-none' }}" data-quick-offer-badge>Daily Offer</span>
                        </div>
                        <div class="small text-success mb-3 {{ $defaultOffer ? '' : 'd-none' }}" data-quick-offer-details>
                            @if ($defaultOffer)
                                {{ number_format($defaultOffer->discountPercentage(), 0) }}% OFF · {{ $defaultOffer->remainingTimeLabel() }} ·
                                @if ($defaultOffer->availableOfferQuantity() > 10)
                                    Limited Daily Offer Stock
                                @elseif ($defaultOffer->availableOfferQuantity() > 3)
                                    Only {{ number_format($defaultOffer->availableOfferQuantity(), 0) }} offer units left
                                @else
                                    Only {{ number_format($defaultOffer->availableOfferQuantity(), 0) }} left at this price
                                @endif
                            @endif
                        </div>
                        @if ($product->short_description)
                            <p class="small text-muted">{{ $product->short_description }}</p>
                        @endif
                        @if ($variant && $variant->status)
                            <form method="POST" action="{{ route('cart.items.store') }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="product_variant_id" value="{{ $variant->id }}" data-quick-variant-id>
                                @if ($defaultOffer)
                                    <input type="hidden" name="daily_offer_id" value="{{ $defaultOffer->id }}" data-quick-daily-offer-id>
                                @else
                                    <input type="hidden" name="daily_offer_id" value="" data-quick-daily-offer-id>
                                @endif
                                <input type="number" name="quantity" value="1" min="1" step="1" max="{{ $product->maximum_order_quantity ?: '' }}" class="form-control form-control-sm" style="max-width: 88px;" data-quick-quantity @disabled(! $variantPurchasable)>
                                <button class="btn btn-success btn-sm" type="submit" data-quick-add @disabled(! $variantPurchasable)>{{ $variantPurchasable ? 'Add to Cart' : 'Out of Stock' }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('change', (event) => {
                if (!event.target.matches('.gk-quick-variant-selector')) {
                    return;
                }

                const selector = event.target;
                const option = selector.selectedOptions[0];
                const modal = document.getElementById(selector.dataset.modalId);

                if (!modal || !option) {
                    return;
                }

                const setText = (selector, value) => {
                    const element = modal.querySelector(selector);

                    if (element) {
                        element.textContent = value;
                    }
                };
                const setValue = (selector, value) => {
                    const element = modal.querySelector(selector);

                    if (element) {
                        element.value = value;
                    }
                };

                setText('[data-quick-variant-name]', option.dataset.name || '');
                setText('[data-quick-price]', 'Rs. ' + (option.dataset.price || ''));
                setText('[data-quick-sku]', option.dataset.sku || '');
                setText('[data-quick-stock]', option.dataset.stock || '0');
                setValue('[data-quick-variant-id]', option.value);
                setValue('[data-quick-daily-offer-id]', option.dataset.dailyOfferId || '');

                const quantity = modal.querySelector('[data-quick-quantity]');

                if (quantity) {
                    quantity.max = option.dataset.maxQuantity || '';
                }

                const normalPrice = modal.querySelector('[data-quick-normal-price]');
                const numericMrp = Number((option.dataset.mrp || '0').replace(/,/g, ''));
                const numericPrice = Number((option.dataset.price || '0').replace(/,/g, ''));
                const showComparePrice = option.dataset.dailyOfferId || numericMrp > numericPrice;

                if (normalPrice) {
                    normalPrice.textContent = option.dataset.dailyOfferId
                        ? 'Rs. ' + (option.dataset.normalPrice || '')
                        : 'Rs. ' + (option.dataset.mrp || '');
                    normalPrice.classList.toggle('d-none', !showComparePrice);
                }

                const badge = modal.querySelector('[data-quick-offer-badge]');

                if (badge) {
                    badge.classList.toggle('d-none', !option.dataset.dailyOfferId);
                }

                const offerDetails = modal.querySelector('[data-quick-offer-details]');

                if (offerDetails) {
                    const details = [
                        option.dataset.offerDiscount ? option.dataset.offerDiscount + '% OFF' : '',
                        option.dataset.offerCountdown || '',
                        option.dataset.offerAvailability || '',
                    ].filter(Boolean).join(' · ');
                    offerDetails.textContent = details;
                    offerDetails.classList.toggle('d-none', !option.dataset.dailyOfferId);
                }

                const inStock = Number(option.dataset.stock || 0) > 0;
                const addButton = modal.querySelector('[data-quick-add]');

                if (addButton) {
                    addButton.disabled = !inStock;
                    addButton.textContent = inStock ? 'Add to Cart' : 'Out of Stock';
                }

                if (quantity) {
                    quantity.disabled = !inStock;
                }

                const image = modal.querySelector('img');

                if (image && option.dataset.image) {
                    image.setAttribute('src', option.dataset.image);
                }
            });
        </script>
    @endpush
@endonce
