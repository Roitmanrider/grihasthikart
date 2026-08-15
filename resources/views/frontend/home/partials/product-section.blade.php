@php($config = $config ?? [])

<section class="gk-section">
    <div class="container">
        <div class="gk-section-panel">
            <div class="gk-section-heading">
                <h2>{{ $config['title'] ?? 'New Products' }}</h2>
                @if ($config['view_all_enabled'] ?? true)
                    <a href="{{ $config['view_all_url'] ?? route('products.index', ['is_new_arrival' => '1']) }}">{{ $config['view_all_text'] ?? 'View All' }}</a>
                @endif
            </div>

            <div class="gk-product-rail">
                @foreach ($products as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </div>
</section>
