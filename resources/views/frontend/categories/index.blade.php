@extends('layouts.frontend')

@section('title', 'Categories - GrihasthiKart')
@section('description', 'Browse active grocery categories.')

@section('content')
    <section class="py-5">
        <div class="container">
            <h1 class="h3 mb-4">Categories</h1>

            @if ($categories->isNotEmpty())
                <div class="row g-4">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-4 col-lg-3">
                            @include('components.category-card', ['category' => $category])
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-light border">No active categories are available.</div>
            @endif
        </div>
    </section>
@endsection
