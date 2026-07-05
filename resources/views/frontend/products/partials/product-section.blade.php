<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">{{ $title }}</h2>
            <a href="{{ route('products.index') }}" class="btn btn-link text-success">View products</a>
        </div>

        @if ($products->isNotEmpty())
            <div class="row g-4">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('components.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-light border">{{ $empty }}</div>
        @endif
    </div>
</section>
