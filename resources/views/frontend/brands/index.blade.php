@extends('layouts.frontend')

@section('title', 'Brands - GrihasthiKart')
@section('description', 'Browse active grocery brands.')

@section('content')
    <section class="py-5">
        <div class="container">
            <h1 class="h3 mb-4">Brands</h1>

            @if ($brands->isNotEmpty())
                <div class="row g-4">
                    @foreach ($brands as $brand)
                        <div class="col-6 col-md-4 col-lg-3">
                            <article class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5">
                                        <a href="{{ route('brands.show', $brand->slug) }}" class="text-dark text-decoration-none">
                                            {{ $brand->name }}
                                        </a>
                                    </h2>
                                    <p class="text-muted small mb-3">{{ $brand->description }}</p>
                                    <div class="small text-success">{{ $brand->products_count }} active products</div>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-light border">No active brands are available.</div>
            @endif
        </div>
    </section>
@endsection
