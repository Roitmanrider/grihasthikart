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
                <div class="alert alert-light border">No active products are available for this brand yet.</div>
            @endif
        </div>
    </section>
@endsection
