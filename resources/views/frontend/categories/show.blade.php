@extends('layouts.frontend')

@section('title', ($category->meta_title ?: $category->name).' - GrihasthiKart')
@section('description', $category->meta_description ?: $category->description)

@section('content')
    <section class="py-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $category->name }}</h1>
                    @if ($category->description)
                        <p class="text-muted mb-0">{{ $category->description }}</p>
                    @endif
                </div>
                <form method="GET" class="d-flex gap-2">
                    <select name="sort" class="form-select">
                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                        <option value="name" @selected(request('sort') === 'name')>Name</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price low to high</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price high to low</option>
                    </select>
                    <button type="submit" class="btn btn-success">Sort</button>
                </form>
            </div>

            @if ($category->children->isNotEmpty())
                <div class="row g-3 mb-5">
                    @foreach ($category->children as $child)
                        <div class="col-6 col-md-3 col-lg-2">
                            @include('components.category-card', ['category' => $child])
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($products->isNotEmpty())
                <div class="row g-4">
                    @foreach ($products as $product)
                        <div class="col-6 col-md-4 col-xl-3">
                            @include('components.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">{{ $products->links() }}</div>
            @else
                <div class="alert alert-light border">No active products are available in this category yet.</div>
            @endif
        </div>
    </section>
@endsection
