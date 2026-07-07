<section class="gk-section gk-home-category-strip" aria-label="All Categories slider">
    <div class="container">
        <div class="gk-home-slider-card">
            <a href="{{ route('categories.index') }}" class="gk-all-categories-tile">
                <i class="fa-solid fa-table-cells-large"></i>
                <span>All Categories</span>
            </a>

            <div class="gk-home-slider-track" data-homepage-slider>
                @foreach ($categories as $category)
                    @include('frontend.home.partials.category-tile', ['category' => $category])
                @endforeach
            </div>

            <button class="gk-home-slider-arrow" type="button" data-slide-next aria-label="Next categories">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>
