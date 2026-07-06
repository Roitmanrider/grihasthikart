<section class="gk-section">
    <div class="container">
        <div class="gk-section-panel {{ ($tone ?? '') === 'offer' ? 'gk-offer-panel' : '' }}">
            <div class="gk-section-heading">
                <h2>
                    @if (! empty($icon))
                        <i class="{{ $icon }}"></i>
                    @endif
                    {{ $title }}
                </h2>
                <a href="{{ route('products.index') }}">View All</a>
            </div>

            @if ($products->isNotEmpty())
                <div class="gk-product-rail">
                    @foreach ($products as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>
            @else
                <x-empty-state :title="$empty" message="Please check back after the catalog team adds products to this section." :action="route('products.index')" action-label="Browse all products" />
            @endif
        </div>
    </div>
</section>
