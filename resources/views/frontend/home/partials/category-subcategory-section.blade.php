<section class="gk-section gk-subcategory-section gk-accent-{{ $accent }}" data-home-category-section>
    <div class="container">
        <div class="gk-subcategory-panel">
            <div class="gk-subcategory-heading">
                <h2>
                    <i class="{{ $category->icon ?: 'fa-solid fa-gift' }}"></i>
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
