<?php

namespace App\Domains\Storefront\Services;

use App\Domains\Catalog\Services\DailyOfferService;
use App\Models\AssociatedPartner;
use App\Models\Category;
use App\Models\HomepageBanner;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomepageContentService
{
    public function __construct(
        private readonly DailyOfferService $dailyOfferService
    ) {}

    public function storefrontData(): array
    {
        $sections = $this->renderSections();

        return [
            'homepageSections' => $sections,
            'categories' => $sections->firstWhere('key', 'shop_by_categories')['categories'] ?? collect(),
            'categorySections' => $sections->where('type', 'category_section')->pluck('category')->values(),
            'dailyOffers' => $sections->firstWhere('key', 'daily_offers')['dailyOffers'] ?? collect(),
            'trustItems' => $this->homepageTrustItems(),
            'partners' => $sections->firstWhere('key', 'associated_partners')['partners'] ?? collect(),
        ];
    }

    public function adminSections(): Collection
    {
        $stored = $this->storedSections()->keyBy('section_key');

        return $this->defaultDefinitions()
            ->map(function (array $definition) use ($stored) {
                $section = $stored->get($definition['section_key']);

                return (object) array_merge($definition, $section?->only([
                    'title',
                    'subtitle',
                    'enabled',
                    'sort_order',
                    'desktop_item_limit',
                    'mobile_item_limit',
                    'source_mode',
                    'root_category_id',
                    'view_all_enabled',
                    'view_all_text',
                    'view_all_url',
                ]) ?? [], [
                    'exists_in_database' => (bool) $section,
                    'model' => $section,
                ]);
            })
            ->sortBy('sort_order')
            ->values();
    }

    public function defaultsFor(string $sectionKey): array
    {
        return $this->defaultDefinitions()->firstWhere('section_key', $sectionKey)
            ?? [
                'section_key' => $sectionKey,
                'section_type' => 'static',
                'title' => str($sectionKey)->replace('_', ' ')->title()->toString(),
                'subtitle' => null,
                'enabled' => true,
                'sort_order' => 100,
                'desktop_item_limit' => 8,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ];
    }

    public function defaultDefinitions(): Collection
    {
        return collect([
            [
                'section_key' => 'banner_slider',
                'section_type' => 'banner',
                'title' => 'Fresh Groceries',
                'subtitle' => 'Delivered to Your Doorstep',
                'enabled' => true,
                'sort_order' => 10,
                'desktop_item_limit' => 5,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'Shop Now',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'shop_by_categories',
                'section_type' => 'category_strip',
                'title' => 'All Categories',
                'subtitle' => null,
                'enabled' => true,
                'sort_order' => 20,
                'desktop_item_limit' => 18,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'All Categories',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'homepage_category_sections',
                'section_type' => 'category_section_group',
                'title' => 'Homepage Categories',
                'subtitle' => null,
                'enabled' => true,
                'sort_order' => 30,
                'desktop_item_limit' => 9,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'fruits_vegetables',
                'section_type' => 'category_section',
                'title' => 'Fruits & Vegetables',
                'subtitle' => null,
                'enabled' => false,
                'sort_order' => 31,
                'desktop_item_limit' => 12,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'foodgrains_flours_rice',
                'section_type' => 'category_section',
                'title' => 'Foodgrains, Flours & Rice',
                'subtitle' => null,
                'enabled' => false,
                'sort_order' => 32,
                'desktop_item_limit' => 12,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'face_body_hair',
                'section_type' => 'category_section',
                'title' => 'Face, Body & Hair Care',
                'subtitle' => null,
                'enabled' => false,
                'sort_order' => 33,
                'desktop_item_limit' => 12,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'new_products',
                'section_type' => 'products',
                'title' => 'New Products',
                'subtitle' => null,
                'enabled' => false,
                'sort_order' => 35,
                'desktop_item_limit' => 8,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'view_more_categories',
                'section_type' => 'cta',
                'title' => 'View More Categories',
                'subtitle' => null,
                'enabled' => true,
                'sort_order' => 40,
                'desktop_item_limit' => 1,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View More Categories',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'daily_offers',
                'section_type' => 'daily_offers',
                'title' => 'Daily Offers',
                'subtitle' => 'Fresh deals updated in '.config('app.timezone'),
                'enabled' => true,
                'sort_order' => 50,
                'desktop_item_limit' => 8,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
            [
                'section_key' => 'trust_icons',
                'section_type' => 'trust',
                'title' => 'Customer Promises',
                'subtitle' => null,
                'enabled' => true,
                'sort_order' => 60,
                'desktop_item_limit' => 5,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => false,
                'view_all_text' => null,
                'view_all_url' => null,
            ],
            [
                'section_key' => 'associated_partners',
                'section_type' => 'partners',
                'title' => 'Our Associated Partners',
                'subtitle' => null,
                'enabled' => true,
                'sort_order' => 70,
                'desktop_item_limit' => 8,
                'mobile_item_limit' => null,
                'source_mode' => 'automatic',
                'root_category_id' => null,
                'view_all_enabled' => true,
                'view_all_text' => 'View All',
                'view_all_url' => null,
            ],
        ]);
    }

    private function renderSections(): Collection
    {
        $configs = $this->resolvedSections();
        $rendered = collect();

        foreach ($configs as $config) {
            if (! $config['enabled']) {
                continue;
            }

            match ($config['section_type']) {
                'banner' => $rendered->push($this->bannerSection($config)),
                'category_strip' => $this->pushIfNotEmpty($rendered, $this->categoryStrip($config), 'categories'),
                'category_section_group' => $rendered = $rendered->merge($this->categorySections($config)),
                'category_section' => $this->pushIfNotEmpty($rendered, $this->configuredCategorySection($config), 'category'),
                'products' => $this->pushIfNotEmpty($rendered, $this->productSection($config), 'products'),
                'cta' => $rendered->push($this->ctaSection($config)),
                'daily_offers' => $rendered->push($this->dailyOffersSection($config)),
                'trust' => $rendered->push($this->trustSection($config)),
                'partners' => $rendered->push($this->partnersSection($config)),
                default => null,
            };
        }

        return $rendered->values();
    }

    private function resolvedSections(): Collection
    {
        $defaults = $this->defaultDefinitions()->keyBy('section_key');

        if (! Schema::hasTable('homepage_sections')) {
            return $defaults->sortBy('sort_order')->values();
        }

        $stored = HomepageSection::query()
            ->with(['rootCategory', 'selectedCategories', 'selectedProducts'])
            ->orderBy('sort_order')
            ->get()
            ->keyBy('section_key');

        return $defaults
            ->merge($stored->map(fn (HomepageSection $section) => $section->toArray()))
            ->map(function (array $definition, string $key) use ($stored, $defaults) {
                $storedSection = $stored->get($key);
                $default = $defaults->get($key, []);

                return array_merge($default, $storedSection?->toArray() ?? $definition, [
                    'model' => $storedSection,
                ]);
            })
            ->sortBy('sort_order')
            ->values();
    }

    private function storedSections(): Collection
    {
        if (! Schema::hasTable('homepage_sections')) {
            return collect();
        }

        return HomepageSection::query()
            ->with(['selectedCategories', 'selectedProducts'])
            ->orderBy('sort_order')
            ->get();
    }

    private function bannerSection(array $config): array
    {
        $banners = Schema::hasTable('homepage_banners')
            ? HomepageBanner::query()->visible()->orderBy('sort_order')->orderBy('id')->limit($config['desktop_item_limit'])->get()
            : collect();

        return [
            'key' => 'banner_slider',
            'type' => 'banner',
            'config' => $config,
            'banners' => $banners,
        ];
    }

    private function categoryStrip(array $config): array
    {
        return [
            'key' => 'shop_by_categories',
            'type' => 'category_strip',
            'config' => $config,
            'categories' => $this->rootCategories()->limit($config['desktop_item_limit'])->get(),
        ];
    }

    private function categorySections(array $config): Collection
    {
        if (Schema::hasTable('homepage_sections')) {
            $configured = HomepageSection::query()
                ->where('section_type', 'category_section')
                ->where('enabled', true)
                ->get();

            if ($configured->isNotEmpty()) {
                return collect();
            }
        }

        return $this->rootCategories()
            ->whereHas('children', fn ($query) => $query->active())
            ->with(['children' => fn ($query) => $query->active()->orderBy('display_order')->orderBy('name')])
            ->limit($config['desktop_item_limit'])
            ->get()
            ->map(fn (Category $category) => [
                'key' => 'category_'.$category->id,
                'type' => 'category_section',
                'config' => $config,
                'category' => $category,
            ]);
    }

    private function configuredCategorySection(array $config): ?array
    {
        $model = $config['model'] ?? null;
        $rootCategoryId = $config['root_category_id'] ?? null;

        if (! $rootCategoryId) {
            return null;
        }

        $selected = $model instanceof HomepageSection
            ? $model->selectedCategories->where('status', true)->whereNull('deleted_at')->values()
            : collect();

        $children = $selected->isNotEmpty()
            ? $selected
            : Category::query()
                ->active()
                ->where('parent_id', $rootCategoryId)
                ->orderBy('display_order')
                ->orderBy('name')
                ->limit($config['desktop_item_limit'])
                ->get();

        if ($children->isEmpty()) {
            return null;
        }

        $category = Category::query()
            ->active()
            ->whereKey($rootCategoryId)
            ->first();

        if (! $category) {
            return null;
        }

        $category->setRelation('children', $children);
        $category->name = $config['title'] ?: $category->name;

        return [
            'key' => $config['section_key'],
            'type' => 'category_section',
            'config' => $config,
            'category' => $category,
        ];
    }

    private function productSection(array $config): ?array
    {
        $products = (($config['source_mode'] ?? 'automatic') === 'manual' && ($config['model'] ?? null) instanceof HomepageSection)
            ? $config['model']->selectedProducts()
                ->where('products.status', true)
                ->whereHas('variants', fn ($query) => $query->active())
                ->with($this->productCardRelations())
                ->limit($config['desktop_item_limit'])
                ->get()
            : $this->productsQuery()
                ->where('is_new_arrival', true)
                ->latest('products.created_at')
                ->limit($config['desktop_item_limit'])
                ->get();

        if ($products->isEmpty()) {
            return null;
        }

        return [
            'key' => $config['section_key'],
            'type' => 'products',
            'config' => $config,
            'products' => $products,
        ];
    }

    private function ctaSection(array $config): array
    {
        return [
            'key' => $config['section_key'],
            'type' => 'cta',
            'config' => $config,
        ];
    }

    private function dailyOffersSection(array $config): array
    {
        return [
            'key' => 'daily_offers',
            'type' => 'daily_offers',
            'config' => $config,
            'dailyOffers' => $this->dailyOfferService->currentOffers($config['desktop_item_limit']),
        ];
    }

    private function trustSection(array $config): array
    {
        return [
            'key' => 'trust_icons',
            'type' => 'trust',
            'config' => $config,
            'items' => $this->homepageTrustItems(),
        ];
    }

    private function partnersSection(array $config): array
    {
        $partners = Schema::hasTable('associated_partners')
            ? AssociatedPartner::query()->visible()->orderBy('sort_order')->orderBy('name')->limit($config['desktop_item_limit'])->get()
            : collect();

        return [
            'key' => 'associated_partners',
            'type' => 'partners',
            'config' => $config,
            'partners' => $partners->isNotEmpty() ? $partners : collect($this->defaultPartners()),
        ];
    }

    private function pushIfNotEmpty(Collection $sections, ?array $section, string $contentKey): void
    {
        if ($section && ! empty($section[$contentKey]) && (! $section[$contentKey] instanceof Collection || $section[$contentKey]->isNotEmpty())) {
            $sections->push($section);
        }
    }

    private function rootCategories(): Builder
    {
        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->orderBy('display_order')
            ->orderBy('name');
    }

    private function productsQuery(): Builder
    {
        return Product::query()
            ->active()
            ->whereHas('variants', fn ($query) => $query->active())
            ->with($this->productCardRelations());
    }

    private function productCardRelations(): array
    {
        return [
            'brand',
            'categories.parent',
            'variants' => fn ($query) => $query->active()->with(['inventories', 'dailyOffers.cartItems.cart', 'dailyOffers.orderItems', 'primaryImage']),
            'defaultVariant.primaryImage',
            'primaryImage',
        ];
    }

    private function homepageTrustItems(): array
    {
        return [
            ['icon' => 'fa-solid fa-truck-fast', 'title' => 'Free Delivery', 'subtitle' => 'On orders above Rs.499'],
            ['icon' => 'fa-regular fa-calendar-check', 'title' => 'Scheduled Delivery', 'subtitle' => 'Choose date & time'],
            ['icon' => 'fa-solid fa-seedling', 'title' => 'Original Products', 'subtitle' => 'Best quality assured'],
            ['icon' => 'fa-solid fa-rotate-left', 'title' => 'Easy Returns', 'subtitle' => 'Hassle free returns'],
            ['icon' => 'fa-solid fa-credit-card', 'title' => 'Payment Options', 'subtitle' => '100% safe & secure'],
        ];
    }

    private function defaultPartners(): array
    {
        return [
            ['name' => 'FreshFarm', 'description' => 'Organics', 'promo_text' => 'UPTO 15% OFF', 'class' => 'fresh'],
            ['name' => 'MilkyDay', 'description' => 'Dairy Products', 'promo_text' => 'UPTO 10% OFF', 'class' => 'dairy'],
            ['name' => 'DailyBasket', 'description' => 'Meat & Seafood', 'promo_text' => 'UPTO 12% OFF', 'class' => 'basket'],
            ['name' => 'PetWorld', 'description' => 'Pet Supplies', 'promo_text' => 'UPTO 8% OFF', 'class' => 'care'],
        ];
    }
}
