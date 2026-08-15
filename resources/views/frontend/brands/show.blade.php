@extends('layouts.frontend')

@section('title', ($brand->meta_title ?: $brand->name).' - GrihasthiKart')
@section('description', $brand->meta_description ?: $brand->description)

@section('content')
    <section class="py-5">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('brands.index') }}">Brands</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $brand->name }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $brand->name }}</h1>
                    @if ($brand->description)
                        <p class="text-muted mb-0">{{ $brand->description }}</p>
                    @endif
                </div>
            </div>

            @include('frontend.products.partials.catalog-filters', [
                'filters' => $filters,
                'filterOptions' => $filterOptions,
                'baseRoute' => route('brands.show', $brand->slug),
            ])

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
                <div class="alert alert-light border">No active products match this brand and filter combination.</div>
            @endif
        </div>
    </section>
@endsection
