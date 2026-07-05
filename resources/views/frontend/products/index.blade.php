@extends('layouts.frontend')

@section('title', 'Products - GrihasthiKart')
@section('description', 'Browse active grocery products with default variant prices.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Products</h1>
                    <p class="text-muted mb-0">Prices are shown from each product's default variant.</p>
                </div>
                <div class="text-muted small">{{ $products->total() }} products</div>
            </div>

            <form method="GET" action="{{ route('products.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label class="form-label" for="search">Search</label>
                    <input type="search" id="search" name="search" value="{{ request('search') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="category">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="brand">Brand</label>
                    <select id="brand" name="brand" class="form-select">
                        <option value="">All</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((string) request('brand') === (string) $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sort">Sort</label>
                    <select id="sort" name="sort" class="form-select">
                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                        <option value="name" @selected(request('sort') === 'name')>Name</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low to high</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high to low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['is_featured' => 'Featured', 'is_trending' => 'Trending', 'is_popular' => 'Popular', 'is_new_arrival' => 'New'] as $field => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(request($field) === '1')>
                                <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Apply filters</button>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

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
                <div class="alert alert-light border">No active products match your filters.</div>
            @endif
        </div>
    </section>
@endsection
