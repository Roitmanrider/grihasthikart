<?php

namespace App\Domains\Storefront\Services;

use App\Domains\Setting\Services\BusinessSettingService;
use Illuminate\Http\Request;

class StorefrontAccessService
{
    public const PUBLIC_STOREFRONT = 'PUBLIC_STOREFRONT';

    public const PUBLIC_BROWSE_MEMBERS_BUY = 'PUBLIC_BROWSE_MEMBERS_BUY';

    public const MEMBERS_ONLY_STOREFRONT = 'MEMBERS_ONLY_STOREFRONT';

    public function __construct(private readonly BusinessSettingService $settings) {}

    public function mode(): string
    {
        $mode = (string) $this->settings->get('storefront.access_mode', self::PUBLIC_BROWSE_MEMBERS_BUY);

        return in_array($mode, $this->modes(), true) ? $mode : self::PUBLIC_BROWSE_MEMBERS_BUY;
    }

    public function homepagePublicInMembersOnly(): bool
    {
        return filter_var($this->settings->get('storefront.homepage_public_in_members_only', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function allowGuestCheckout(): bool
    {
        return filter_var($this->settings->get('storefront.allow_guest_checkout', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function requiresLogin(Request $request, string $classification): bool
    {
        if ($classification === 'transactional') {
            return $this->mode() !== self::PUBLIC_STOREFRONT || ! $this->allowGuestCheckout();
        }

        if ($classification === 'catalog') {
            return $this->mode() === self::MEMBERS_ONLY_STOREFRONT;
        }

        if ($classification === 'home') {
            return $this->mode() === self::MEMBERS_ONLY_STOREFRONT && ! $this->homepagePublicInMembersOnly();
        }

        return false;
    }

    public function seoStatus(): array
    {
        $membersOnly = $this->mode() === self::MEMBERS_ONLY_STOREFRONT;
        $homepagePublic = ! $membersOnly || $this->homepagePublicInMembersOnly();

        return [
            'storefront_access_mode' => $this->mode(),
            'homepage_public' => $homepagePublic,
            'product_public_indexing' => ! $membersOnly,
            'category_public_indexing' => ! $membersOnly,
            'public_search_pages' => ! $membersOnly,
            'future_sitemap_inclusion' => ! $membersOnly,
            'protected_pages_noindex' => $membersOnly,
            'google_ads_landing_suitability' => $this->googleAdsSuitability(),
            'reason' => $membersOnly
                ? 'Members-Only Storefront restricts public catalog access. Product/category indexing and some SEO/Google Ads landing-page capabilities will be limited.'
                : 'Catalog and product pages are publicly browseable and suitable for normal indexing.',
        ];
    }

    public function shouldNoindex(Request $request): bool
    {
        if ($request->routeIs('products.index', 'categories.show', 'brands.show')
            && collect(['q', 'search', 'brand', 'brands', 'min_price', 'max_price', 'weight', 'weights', 'discount_min', 'sort', 'page'])
                ->contains(fn (string $key) => $request->query->has($key))) {
            return true;
        }

        return $this->mode() === self::MEMBERS_ONLY_STOREFRONT
            && $request->routeIs('products.*', 'categories.*', 'brands.*', 'daily-offers.*');
    }

    public function modes(): array
    {
        return [
            self::PUBLIC_STOREFRONT,
            self::PUBLIC_BROWSE_MEMBERS_BUY,
            self::MEMBERS_ONLY_STOREFRONT,
        ];
    }

    public function modeLabels(): array
    {
        return [
            self::PUBLIC_STOREFRONT => 'Public Storefront',
            self::PUBLIC_BROWSE_MEMBERS_BUY => 'Public Browse / Members Buy',
            self::MEMBERS_ONLY_STOREFRONT => 'Members-Only Storefront',
        ];
    }

    private function googleAdsSuitability(): string
    {
        if ($this->mode() !== self::MEMBERS_ONLY_STOREFRONT) {
            return 'Normal';
        }

        return $this->homepagePublicInMembersOnly()
            ? 'Limited - public landing pages only'
            : 'Restricted';
    }
}
