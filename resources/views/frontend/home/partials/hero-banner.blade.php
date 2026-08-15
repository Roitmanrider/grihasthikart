@php
    $mediaResolver = app(\App\Services\MediaResolver::class);
    $banners = $banners ?? collect();
    $config = $config ?? [];
@endphp

<section class="gk-hero gk-home-hero">
    <button class="gk-hero-arrow gk-hero-arrow-left" type="button" aria-label="Previous banner">
        <i class="fa-solid fa-chevron-left"></i>
    </button>

    <div class="container">
        @if ($banners->isNotEmpty())
            @foreach ($banners as $banner)
                <div class="gk-hero-card {{ ! $loop->first ? 'd-none' : '' }}">
                    <div class="gk-hero-copy">
                        <h1>{{ $banner->title ?: ($config['title'] ?? 'Fresh Groceries') }}</h1>
                        @if ($banner->subtitle)
                            <h2>{{ $banner->subtitle }}</h2>
                        @endif
                        <p>Best Quality <span></span> Best Price <span></span> On Time</p>
                        @if ($banner->cta_text && $banner->cta_url)
                            <div class="gk-hero-actions">
                                <a href="{{ $banner->cta_url }}" class="btn btn-success" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>{{ $banner->cta_text }}</a>
                            </div>
                        @endif
                    </div>
                    <div class="gk-hero-image">
                        <span>{{ $banner->alt_text ?: 'Original & Fresh' }}</span>
                        <picture>
                            <source media="(max-width: 767px)" srcset="{{ $mediaResolver->url($banner->mobile_image_path ?: $banner->desktop_image_path) }}">
                            <img src="{{ $mediaResolver->url($banner->desktop_image_path) }}" alt="{{ $banner->alt_text ?: $banner->title ?: 'GrihasthiKart banner' }}">
                        </picture>
                    </div>
                </div>
            @endforeach
        @else
            <div class="gk-hero-card">
                <div class="gk-hero-copy">
                    <h1>{{ $config['title'] ?? 'Fresh Groceries' }}</h1>
                    <h2>{{ $config['subtitle'] ?? 'Delivered to Your Doorstep' }}</h2>
                    <p>Best Quality <span></span> Best Price <span></span> On Time</p>
                    <div class="gk-hero-actions">
                        <a href="{{ route('products.index') }}" class="btn btn-success">{{ $config['view_all_text'] ?? 'Shop Now' }}</a>
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
        @endif
        <div class="gk-slider-dots" aria-hidden="true">
            @forelse ($banners as $banner)
                <span class="{{ $loop->first ? 'active' : '' }}"></span>
            @empty
                <span class="active"></span><span></span><span></span><span></span><span></span>
            @endforelse
        </div>
    </div>

    <button class="gk-hero-arrow gk-hero-arrow-right" type="button" aria-label="Next banner">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
</section>
