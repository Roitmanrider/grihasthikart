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
            </div>

            @include('frontend.products.partials.catalog-filters', [
                'filters' => $filters,
                'filterOptions' => $filterOptions,
                'baseRoute' => route('categories.show', $category->slug),
            ])

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
                <div class="alert alert-light border">No active products match this category and filter combination.</div>
            @endif
        </div>
    </section>
@endsection
