@php
    $loadingImage = app(\App\Domains\Setting\Services\BusinessSettingService::class)->get('site.loading_image_path');
    $loadingImageUrl = app(\App\Services\MediaResolver::class)->url($loadingImage);
@endphp

<div class="gk-loading-overlay" data-loading-overlay hidden>
    <div class="gk-loading-card">
        @if ($loadingImageUrl)
            <img src="{{ $loadingImageUrl }}" alt="Loading" class="gk-loading-image">
        @else
            <div class="gk-grocery-loader" aria-hidden="true">
                <i class="fa-solid fa-basket-shopping"></i>
                <span></span>
            </div>
        @endif
        <div class="small fw-semibold text-success mt-2">Please wait...</div>
    </div>
</div>
