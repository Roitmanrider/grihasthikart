<section class="gk-hero gk-home-hero">
    <button class="gk-hero-arrow gk-hero-arrow-left" type="button" aria-label="Previous banner">
        <i class="fa-solid fa-chevron-left"></i>
    </button>

    <div class="container">
        <div class="gk-hero-card">
            <div class="gk-hero-copy">
                <h1>Fresh Groceries</h1>
                <h2>Delivered to Your Doorstep</h2>
                <p>Best Quality <span></span> Best Price <span></span> On Time</p>
                <div class="gk-hero-actions">
                    <a href="{{ route('products.index') }}" class="btn btn-success">Shop Now</a>
                    <div class="gk-delivery-note">
                        <i class="fa-solid fa-truck-fast"></i>
                        <div>
                            <strong>Free Delivery</strong>
                            <small>On Orders Above Rs.499</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="gk-hero-image">
                <span>Original & Fresh</span>
                <img src="{{ asset('assets/images/hero/hero-1.webp') }}" alt="Fresh groceries basket">
            </div>
        </div>
        <div class="gk-slider-dots" aria-hidden="true">
            <span class="active"></span><span></span><span></span><span></span><span></span>
        </div>
    </div>

    <button class="gk-hero-arrow gk-hero-arrow-right" type="button" aria-label="Next banner">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
</section>
