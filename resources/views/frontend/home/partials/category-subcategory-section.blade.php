@php
    $categoryKey = \Illuminate\Support\Str::lower($category->slug.' '.$category->name);
    $mediaResolver = app(\App\Services\MediaResolver::class);
    $fallbackIcon = match (true) {
        str_contains($categoryKey, 'fruit') || str_contains($categoryKey, 'vegetable') => 'fa-solid fa-leaf',
        str_contains($categoryKey, 'grain') || str_contains($categoryKey, 'flour') || str_contains($categoryKey, 'rice') || str_contains($categoryKey, 'atta') => 'fa-solid fa-wheat-awn',
        str_contains($categoryKey, 'face') || str_contains($categoryKey, 'body') || str_contains($categoryKey, 'hair') || str_contains($categoryKey, 'personal') => 'fa-solid fa-pump-soap',
        str_contains($categoryKey, 'dairy') || str_contains($categoryKey, 'milk') => 'fa-solid fa-bottle-water',
        str_contains($categoryKey, 'snack') || str_contains($categoryKey, 'biscuit') || str_contains($categoryKey, 'cookie') => 'fa-solid fa-cookie-bite',
        str_contains($categoryKey, 'oil') || str_contains($categoryKey, 'ghee') => 'fa-solid fa-droplet',
        str_contains($categoryKey, 'baby') => 'fa-solid fa-baby',
        str_contains($categoryKey, 'home') || str_contains($categoryKey, 'clean') => 'fa-solid fa-house-chimney',
        str_contains($categoryKey, 'pet') => 'fa-solid fa-paw',
        default => 'fa-solid fa-basket-shopping',
    };
@endphp

<section class="gk-section gk-subcategory-section gk-accent-{{ $accent }}" data-home-category-section>
    <div class="container">
        <div class="gk-subcategory-panel">
            <div class="gk-subcategory-heading">
                <h2>
                    @if ($category->image)
                        <img class="gk-heading-category-image" src="{{ $mediaResolver->url($category->image) }}" alt="{{ $category->name }}">
                    @else
                        <i class="{{ $category->icon ?: $fallbackIcon }}"></i>
                    @endif
                    {{ $category->name }}
                </h2>
                <a href="{{ route('categories.show', $category->slug) }}">View All</a>
            </div>

            <div class="gk-subcategory-body">
                <button class="gk-row-arrow gk-row-arrow-left" type="button" data-slide-prev aria-label="Previous {{ $category->name }}">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <div class="gk-home-slider-track gk-subcategory-track" data-homepage-slider>
                    @foreach ($category->children as $child)
                        @include('frontend.home.partials.category-tile', ['category' => $child])
                    @endforeach
                </div>

                <button class="gk-row-arrow gk-row-arrow-right" type="button" data-slide-next aria-label="Next {{ $category->name }}">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</section>
