<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Storefront\Services\HomepageContentService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class CustomerCatalogService
{
    public function __construct(
        private readonly DailyOfferService $dailyOfferService,
        private readonly HomepageContentService $homepageContentService
    ) {}

    public function homepageData(): array
    {
        return $this->homepageContentService->storefrontData();
    }

    public function productListing(array $filters = [], int $perPage = 12)
    {
        $query = $this->customerProductsQuery()
            ->search($filters['search'] ?? null);

        if (($filters['category'] ?? null) !== null && $filters['category'] !== '') {
            $query->whereHas('categories', fn ($query) => $query->whereKey((int) $filters['category']));
        }

        if (($filters['brand'] ?? null) !== null && $filters['brand'] !== '') {
            $query->where('brand_id', (int) $filters['brand']);
        }

        foreach (['is_featured', 'is_new_arrival', 'is_popular', 'is_trending'] as $flag) {
            if (($filters[$flag] ?? null) === '1') {
                $query->where($flag, true);
            }
        }

        $this->applySort($query, $filters['sort'] ?? 'latest');

        return $query->paginate($perPage)->withQueryString();
    }

    public function productDetail(string $slug): Product
    {
        return Product::query()
            ->active()
            ->where('slug', $slug)
            ->whereHas('variants', fn ($query) => $query->active())
            ->with([
                'brand',
                'categories',
                'images' => fn ($query) => $query->active(),
                'primaryImage',
                'defaultVariant.primaryImage',
                'variants' => fn ($query) => $query->active()->with(['attributeValues.attribute', 'inventories', 'dailyOffers.cartItems.cart', 'dailyOffers.orderItems', 'images' => fn ($query) => $query->active(), 'primaryImage']),
            ])
            ->firstOrFail();
    }

    public function categoryListing()
    {
        return $this->activeCategories()
            ->with(['children' => fn ($query) => $query->active()])
            ->whereNull('parent_id')
            ->get();
    }

    public function categoryDetail(string $slug, array $filters = [], int $perPage = 12): array
    {
        $category = Category::query()
            ->active()
            ->where('slug', $slug)
            ->with(['children' => fn ($query) => $query->active(), 'parent'])
            ->firstOrFail();

        $categoryIds = $this->categoryAndChildIds($category);
        $query = $this->customerProductsQuery()
            ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $categoryIds));

        $this->applySort($query, $filters['sort'] ?? 'latest');

        return [
            'category' => $category,
            'products' => $query->paginate($perPage)->withQueryString(),
        ];
    }

    public function brandListing()
    {
        return Brand::query()
            ->active()
            ->withCount(['products' => fn ($query) => $query->active()])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function brandDetail(string $slug, array $filters = [], int $perPage = 12): array
    {
        $brand = Brand::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $query = $this->customerProductsQuery()
            ->where('brand_id', $brand->id);

        $this->applySort($query, $filters['sort'] ?? 'latest');

        return [
            'brand' => $brand,
            'products' => $query->paginate($perPage)->withQueryString(),
        ];
    }

    public function activeCategories()
    {
        return Category::query()
            ->active()
            ->orderBy('display_order')
            ->orderBy('name');
    }

    public function activeBrands()
    {
        return Brand::query()
            ->active()
            ->orderBy('display_order')
            ->orderBy('name');
    }

    private function customerProductsQuery(): Builder
    {
        return Product::query()
            ->active()
            ->whereHas('variants', fn ($query) => $query->active())
            ->with([
                'brand',
                'categories.parent',
                'variants' => fn ($query) => $query->active()->with(['inventories', 'dailyOffers.cartItems.cart', 'dailyOffers.orderItems', 'primaryImage']),
                'defaultVariant.primaryImage',
                'primaryImage',
            ]);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name' => $query->orderBy('name'),
            'price_asc' => $query->join('product_variants as default_variants', 'products.default_variant_id', '=', 'default_variants.id')
                ->orderBy('default_variants.selling_price')
                ->select('products.*'),
            'price_desc' => $query->join('product_variants as default_variants', 'products.default_variant_id', '=', 'default_variants.id')
                ->orderByDesc('default_variants.selling_price')
                ->select('products.*'),
            default => $query->latest('products.created_at'),
        };
    }

    private function categoryAndChildIds(Category $category): array
    {
        $ids = [$category->id];

        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->categoryAndChildIds($child));
        }

        return array_values(array_unique($ids));
    }
}
