@extends('layouts.frontend')

@section('title', 'GrihasthiKart - Fresh Groceries Delivered')
@section('description', 'Fresh groceries, daily offers, grocery categories, and household essentials delivered by GrihasthiKart.')

@section('content')
    @foreach ($homepageSections as $section)
        @switch($section['type'])
            @case('banner')
                @include('frontend.home.partials.hero-banner', ['banners' => $section['banners'], 'config' => $section['config']])
                @break

            @case('category_strip')
                @include('frontend.home.partials.all-categories-strip', ['categories' => $section['categories'], 'config' => $section['config']])
                @break

            @case('category_section')
                @include('frontend.home.partials.category-subcategory-section', [
                    'category' => $section['category'],
                    'config' => $section['config'],
                    'accent' => ['green', 'amber', 'violet', 'blue', 'rose', 'teal', 'lime', 'peach', 'mint'][$loop->index % 9],
                ])
                @break

            @case('products')
                @include('frontend.home.partials.product-section', ['products' => $section['products'], 'config' => $section['config']])
                @break

            @case('cta')
                @include('frontend.home.partials.view-more-categories', ['config' => $section['config']])
                @break

            @case('daily_offers')
                @include('frontend.home.partials.daily-offers', ['dailyOffers' => $section['dailyOffers'], 'config' => $section['config']])
                @break

            @case('trust')
                @include('frontend.home.partials.trust-icons', ['items' => $section['items']])
                @break

            @case('partners')
                @include('frontend.home.partials.partners', ['partners' => $section['partners'], 'config' => $section['config']])
                @break
        @endswitch
    @endforeach
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/homepage-sliders.js') }}"></script>
@endpush
