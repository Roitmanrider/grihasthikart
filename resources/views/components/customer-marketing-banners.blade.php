@php
    $bannerCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $banners = app(\App\Domains\Marketing\Services\CustomerMarketingBannerService::class)->applicableFor($bannerCustomer, 5);
@endphp

@if ($banners->isNotEmpty())
    <section class="container my-4" aria-label="Customer offers">
        <div id="customerMarketingBanners" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4500">
            <div class="carousel-inner rounded-2 overflow-hidden">
                @foreach ($banners as $banner)
                    <div class="carousel-item @if($loop->first) active @endif">
                        <a href="{{ $banner->cta_url ?: '#' }}" class="d-block text-decoration-none text-white position-relative">
                            <picture>
                                @if ($banner->mobile_image_path)
                                    <source media="(max-width: 767px)" srcset="{{ asset($banner->mobile_image_path) }}">
                                @endif
                                <img src="{{ asset($banner->image_path) }}" class="d-block w-100" alt="{{ $banner->title ?: 'Customer offer' }}" style="max-height: 220px; object-fit: cover;">
                            </picture>
                            @if ($banner->title || $banner->subtitle || $banner->cta_text)
                                <span class="position-absolute bottom-0 start-0 end-0 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,.65));">
                                    @if ($banner->title)
                                        <span class="d-block fw-semibold">{{ $banner->title }}</span>
                                    @endif
                                    @if ($banner->subtitle)
                                        <span class="d-block small">{{ $banner->subtitle }}</span>
                                    @endif
                                    @if ($banner->cta_text)
                                        <span class="btn btn-sm btn-light mt-2">{{ $banner->cta_text }}</span>
                                    @endif
                                </span>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>
            @if ($banners->count() > 1)
                <button class="carousel-control-prev" type="button" data-bs-target="#customerMarketingBanners" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#customerMarketingBanners" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            @endif
        </div>
    </section>
@endif
