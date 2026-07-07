@extends('layouts.frontend')

@section('title', 'GrihasthiKart - Fresh Groceries Delivered')
@section('description', 'Fresh groceries, daily offers, grocery categories, and household essentials delivered by GrihasthiKart.')

@section('content')
    @include('frontend.home.partials.hero-banner')

    @include('frontend.home.partials.all-categories-strip', ['categories' => $categories])

    @foreach ($categorySections as $category)
        @include('frontend.home.partials.category-subcategory-section', [
            'category' => $category,
            'accent' => ['green', 'amber', 'violet', 'blue', 'rose', 'teal', 'lime', 'peach', 'mint'][$loop->index % 9],
        ])
    @endforeach

    <section class="gk-section gk-view-more-categories">
        <div class="container text-center">
            <a href="{{ route('categories.index') }}" class="btn btn-outline-success">View More Categories</a>
        </div>
    </section>

    @include('frontend.home.partials.daily-offers', ['dailyOffers' => $dailyOffers])

    @include('frontend.home.partials.trust-icons', ['items' => $trustItems])

    @include('frontend.home.partials.partners', ['partners' => $partners])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/homepage-sliders.js') }}"></script>
@endpush
