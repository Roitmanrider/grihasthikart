@extends('layouts.frontend')

@section('title', 'Products - GrihasthiKart')
@section('description', 'Browse active grocery products with default variant prices.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ ($filters['q'] ?? '') !== '' ? 'Search Results' : 'Products' }}</h1>
                    <p class="text-muted mb-0">
                        @if (($filters['q'] ?? '') !== '')
                            Showing catalog results for "{{ $filters['q'] }}".
                        @else
                            Prices are shown from each product's current storefront variant.
                        @endif
                    </p>
                </div>
                <div class="text-muted small">{{ $products->total() }} products</div>
            </div>

            @if (($categorySuggestions ?? collect())->isNotEmpty())
                <div class="mb-4">
                    <div class="small text-muted mb-2">Related categories</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($categorySuggestions as $suggestion)
                            <a href="{{ route('categories.show', $suggestion->slug) }}" class="btn btn-sm btn-outline-success">{{ $suggestion->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            @include('frontend.products.partials.catalog-filters', [
                'filters' => $filters,
                'filterOptions' => $filterOptions,
                'baseRoute' => route('products.index'),
            ])

            @if ($products->isNotEmpty())
                <div class="row g-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-md-4 col-xl-3">
                            @include('components.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            @else
                <div class="alert alert-light border">
                    <h2 class="h6">No products found{{ ($filters['q'] ?? '') !== '' ? ' for "'.$filters['q'].'"' : '' }}.</h2>
                    <div class="text-muted mb-3">Try clearing filters or browse categories.</div>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-success btn-sm">Browse Categories</a>
                    <a href="{{ route('products.index', ($filters['q'] ?? '') !== '' ? ['q' => $filters['q']] : []) }}" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            @endif
        </div>
    </section>
@endsection
