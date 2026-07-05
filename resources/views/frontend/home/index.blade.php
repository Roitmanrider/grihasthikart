@extends('layouts.frontend')

@section('title', 'GrihasthiKart - Fresh grocery catalog')
@section('description', 'Browse grocery categories, brands, products, and product variants at GrihasthiKart.')

@section('content')
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <p class="text-success fw-semibold mb-2">Fresh groceries, organized for easy browsing</p>
                    <h1 class="display-5 fw-bold">GrihasthiKart</h1>
                    <p class="lead text-muted">Explore Indian grocery staples by category, brand, and pack variant.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('products.index') }}" class="btn btn-success">Browse products</a>
                        <a href="{{ route('categories.index') }}" class="btn btn-outline-success">Shop by category</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="h3 text-success mb-0">{{ $categories->count() }}</div>
                                <div class="small text-muted">Categories</div>
                            </div>
                            <div class="col-6">
                                <div class="h3 text-success mb-0">{{ $featuredProducts->count() }}</div>
                                <div class="small text-muted">Featured picks</div>
                            </div>
                            <div class="col-6">
                                <div class="h3 text-success mb-0">{{ $newArrivals->count() }}</div>
                                <div class="small text-muted">New arrivals</div>
                            </div>
                            <div class="col-6">
                                <div class="h3 text-success mb-0">{{ $trendingProducts->count() }}</div>
                                <div class="small text-muted">Trending</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="py-5">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">Shop By Category</h2>
                    <a href="{{ route('categories.index') }}" class="btn btn-link text-success">View all</a>
                </div>
                <div class="row g-4">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-4 col-lg-2">
                            @include('components.category-card', ['category' => $category])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('frontend.products.partials.product-section', [
        'title' => 'Featured Products',
        'products' => $featuredProducts,
        'empty' => 'Featured products are coming soon.',
    ])

    @include('frontend.products.partials.product-section', [
        'title' => 'New Arrivals',
        'products' => $newArrivals,
        'empty' => 'New arrivals are coming soon.',
    ])

    @include('frontend.products.partials.product-section', [
        'title' => 'Trending Products',
        'products' => $trendingProducts,
        'empty' => 'Trending products are coming soon.',
    ])

    @include('frontend.products.partials.product-section', [
        'title' => 'Popular Products',
        'products' => $popularProducts,
        'empty' => 'Popular products are coming soon.',
    ])
@endsection
