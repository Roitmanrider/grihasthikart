@extends('layouts.admin')

@section('title','Storefront & SEO')

@section('admin-content')
@php
    $savedMode = $settings['access_mode'];
    $selectedMode = old('access_mode', $savedMode);
    $homepagePublic = old('homepage_public_in_members_only', $settings['homepage_public_in_members_only']);
    $allowGuestCheckout = old('allow_guest_checkout', $settings['allow_guest_checkout']);
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Storefront & SEO</h1>
        <div class="text-muted">Access mode, guest checkout policy, and public visibility status.</div>
    </div>
</div>
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
<form method="POST" action="{{ route('admin.settings.storefront-seo.update') }}" class="row g-3">
@csrf @method('PUT')
<div class="col-12">
    <div class="text-muted small">Current Active Mode: <span class="fw-semibold text-body">{{ $modeLabels[$savedMode] ?? $savedMode }}</span></div>
</div>
<div class="col-md-6">
    <label class="form-label" for="access_mode">Change Storefront Access Mode</label>
    <select name="access_mode" id="access_mode" class="form-select @error('access_mode') is-invalid @enderror" data-saved-mode="{{ $savedMode }}">
        @foreach ($modeLabels as $value => $label)
            <option value="{{ $value }}" @selected($selectedMode === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('access_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-12 d-none" id="unsavedModeNotice">
    <div class="alert alert-info mb-0">Unsaved change — SEO status below reflects the currently active saved setting.</div>
</div>
<div class="col-12 {{ $selectedMode !== 'PUBLIC_STOREFRONT' ? 'd-none' : '' }}" id="guestCheckoutSection">
    <div class="border rounded p-3">
        <input type="hidden" name="allow_guest_checkout" value="0">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="allow_guest_checkout" value="1" id="allow_guest_checkout" @checked((bool) $allowGuestCheckout)>
            <label class="form-check-label fw-semibold" for="allow_guest_checkout">Allow Guest Checkout</label>
        </div>
        <div class="text-muted small mt-2">Guest checkout allows orders without a customer login. Existing guest order validation still applies.</div>
        <div class="alert alert-warning mt-3 mb-0">
            <div class="form-check">
                <input class="form-check-input @error('guest_checkout_acknowledged') is-invalid @enderror" type="checkbox" name="guest_checkout_acknowledged" value="1" id="guest_checkout_acknowledged">
                <label class="form-check-label" for="guest_checkout_acknowledged">I understand that guest checkout allows orders without an admin-approved customer address.</label>
                @error('guest_checkout_acknowledged')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
<div class="col-12 {{ $selectedMode !== 'MEMBERS_ONLY_STOREFRONT' ? 'd-none' : '' }}" id="homepagePublicSection">
    <div class="form-check">
        <input type="hidden" name="homepage_public_in_members_only" value="0">
        <input class="form-check-input" type="checkbox" name="homepage_public_in_members_only" value="1" id="homepage_public_in_members_only" @checked((bool) $homepagePublic)>
        <label class="form-check-label" for="homepage_public_in_members_only">Homepage public in Members-Only mode</label>
    </div>
</div>
<div class="col-12 {{ $selectedMode !== 'MEMBERS_ONLY_STOREFRONT' ? 'd-none' : '' }}" id="membersOnlyWarning">
    <div class="alert alert-warning mb-0">
        Members-Only Storefront restricts public catalog access. Product/category indexing and some SEO/Google Ads landing-page capabilities will be limited.
        <div class="form-check mt-2">
            <input class="form-check-input @error('members_only_seo_acknowledged') is-invalid @enderror" type="checkbox" name="members_only_seo_acknowledged" value="1" id="members_only_seo_acknowledged">
            <label class="form-check-label" for="members_only_seo_acknowledged">I understand that Members-Only mode restricts public SEO visibility.</label>
            @error('members_only_seo_acknowledged')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
<div class="col-12"><button class="btn btn-success">Save Settings</button></div>
</form>
</div></div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">SEO & Storefront Visibility</div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-4"><span class="text-muted d-block">Storefront Access Mode</span><strong>{{ $modeLabels[$seoStatus['storefront_access_mode']] ?? $seoStatus['storefront_access_mode'] }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Homepage Public</span><strong>{{ $seoStatus['homepage_public'] ? 'Yes' : 'No' }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Product Public Indexing</span><strong>{{ $seoStatus['product_public_indexing'] ? 'Enabled' : 'Disabled' }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Category Public Indexing</span><strong>{{ $seoStatus['category_public_indexing'] ? 'Enabled' : 'Disabled' }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Public Search Pages</span><strong>{{ $seoStatus['public_search_pages'] ? 'Enabled' : 'Restricted' }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Future Sitemap Inclusion</span><strong>{{ $seoStatus['future_sitemap_inclusion'] ? 'Eligible' : 'Excluded' }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Protected Pages noindex</span><strong>{{ $seoStatus['protected_pages_noindex'] ? 'Yes' : 'No' }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">Google Ads Landing Suitability</span><strong>{{ $seoStatus['google_ads_landing_suitability'] }}</strong></div>
        </div>
        <div class="text-muted small mt-3">{{ $seoStatus['reason'] }}</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mode = document.getElementById('access_mode');
    const guest = document.getElementById('guestCheckoutSection');
    const homepage = document.getElementById('homepagePublicSection');
    const warning = document.getElementById('membersOnlyWarning');
    const notice = document.getElementById('unsavedModeNotice');

    const refresh = () => {
        const value = mode.value;
        guest.classList.toggle('d-none', value !== 'PUBLIC_STOREFRONT');
        homepage.classList.toggle('d-none', value !== 'MEMBERS_ONLY_STOREFRONT');
        warning.classList.toggle('d-none', value !== 'MEMBERS_ONLY_STOREFRONT');
        notice.classList.toggle('d-none', value === mode.dataset.savedMode);
    };

    mode.addEventListener('change', refresh);
    refresh();
});
</script>
@endsection
