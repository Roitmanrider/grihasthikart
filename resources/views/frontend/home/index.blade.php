@extends('layouts.frontend')

@section('title', 'GrihasthiKart - Fresh Groceries Delivered')
@section('description', 'Fresh groceries, daily offers, grocery categories, and household essentials delivered by GrihasthiKart.')

@section('content')
    <section class="gk-hero">
        <div class="container">
            <div class="gk-hero-card">
                <div class="gk-hero-copy">
                    <h1>Fresh Groceries</h1>
                    <h2>Delivered to Your Doorstep</h2>
                    <p>Best Quality <span></span> Best Price <span></span> On Time</p>
                    <div class="gk-hero-actions">
                        <a href="{{ route('products.index') }}" class="btn btn-success">Shop Now</a>
                        <div class="gk-delivery-note">
                            <i class="fa-solid fa-truck-fast"></i>
                            <div>
                                <strong>Free Delivery</strong>
                                <small>On Orders Above Rs.499</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gk-hero-image">
                    <span>Original & Fresh</span>
                    <img src="{{ asset('assets/images/hero/hero-1.webp') }}" alt="Fresh groceries basket">
                </div>
            </div>
            <div class="gk-slider-dots" aria-hidden="true">
                <span class="active"></span><span></span><span></span><span></span><span></span>
            </div>
        </div>
    </section>

    <section class="gk-section gk-category-strip">
        <div class="container">
            <div class="gk-category-grid">
                <a href="{{ route('categories.index') }}" class="gk-all-categories">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>All Categories</span>
                </a>
                @foreach ($categories->take(8) as $category)
                    @include('components.category-card', ['category' => $category])
                @endforeach
                <a href="{{ route('categories.index') }}" class="gk-next-pill" aria-label="View all categories">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="gk-section">
            <div class="container">
                <div class="gk-section-panel">
                    <div class="gk-section-heading">
                        <h2><i class="fa-solid fa-gift"></i> Shop by Categories</h2>
                        <a href="{{ route('categories.index') }}">View All</a>
                    </div>
                    <div class="gk-category-rail">
                        @foreach ($categories->take(6) as $category)
                            @include('components.category-card', ['category' => $category])
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="gk-section">
        <div class="container">
            <div class="gk-offers-strip">
                <div><i class="fa-solid fa-truck-fast"></i><strong>Free Delivery</strong><span>On orders above Rs.499</span></div>
                <div><i class="fa-regular fa-calendar-check"></i><strong>Scheduled Delivery</strong><span>Choose date & time</span></div>
                <div><i class="fa-solid fa-seedling"></i><strong>Original Products</strong><span>Best quality assured</span></div>
                <div><i class="fa-solid fa-rotate-left"></i><strong>Easy Returns</strong><span>Hassle free returns</span></div>
                <div><i class="fa-solid fa-credit-card"></i><strong>Payment Options</strong><span>100% safe & secure</span></div>
            </div>
        </div>
    </section>

    @include('frontend.products.partials.product-section', [
        'title' => 'Featured Products',
        'icon' => 'fa-solid fa-tags',
        'products' => $featuredProducts,
        'empty' => 'Featured products are coming soon.',
        'tone' => 'offer',
    ])

    @include('frontend.products.partials.product-section', [
        'title' => 'New Arrivals',
        'icon' => 'fa-solid fa-star',
        'products' => $newArrivals,
        'empty' => 'New arrivals are coming soon.',
    ])

    @include('frontend.products.partials.product-section', [
        'title' => 'Trending Products',
        'icon' => 'fa-solid fa-chart-line',
        'products' => $trendingProducts,
        'empty' => 'Trending products are coming soon.',
    ])

    @include('frontend.products.partials.product-section', [
        'title' => 'Popular Products',
        'icon' => 'fa-solid fa-heart',
        'products' => $popularProducts,
        'empty' => 'Popular products are coming soon.',
    ])

    <section class="gk-section gk-partners">
        <div class="container">
            <div class="gk-section-heading gk-heading-plain">
                <h2>Our Associated Partners</h2>
                <a href="{{ route('brands.index') }}">View All</a>
            </div>
            <div class="gk-partner-grid">
                <a href="{{ route('brands.index') }}" class="gk-partner-card fresh">
                    <strong>FreshFarm</strong>
                    <span>Organics</span>
                    <em>UPTO 15% OFF</em>
                </a>
                <a href="{{ route('brands.index') }}" class="gk-partner-card dairy">
                    <strong>MilkyDay</strong>
                    <span>Dairy Products</span>
                    <em>UPTO 10% OFF</em>
                </a>
                <a href="{{ route('brands.index') }}" class="gk-partner-card basket">
                    <strong>DailyBasket</strong>
                    <span>Meat & Seafood</span>
                    <em>UPTO 12% OFF</em>
                </a>
                <a href="{{ route('brands.index') }}" class="gk-partner-card care">
                    <strong>PetWorld</strong>
                    <span>Pet Supplies</span>
                    <em>UPTO 8% OFF</em>
                </a>
            </div>
        </div>
    </section>
@endsection
