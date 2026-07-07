<?php

namespace App\Domains\Catalog\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class CustomerCatalogService
{
    public function homepageData(): array
    {
        $categories = $this->activeCategories()
            ->withCount(['children' => fn ($query) => $query->active()])
            ->whereNull('parent_id')
            ->take(18)
            ->get();

        return [
            'categories' => $categories,
            'categorySections' => $this->homepageCategorySections(),
            'dailyOffers' => $this->customerProductsQuery()->where('is_featured', true)->take(8)->get(),
            'trustItems' => $this->homepageTrustItems(),
            'partners' => $this->homepagePartners(),
        ];
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
            ->whereHas('defaultVariant', fn ($query) => $query->active())
            ->with([
                'brand',
                'categories',
                'images' => fn ($query) => $query->active(),
                'primaryImage',
                'defaultVariant.primaryImage',
                'variants' => fn ($query) => $query->active()->with(['attributeValues.attribute', 'images' => fn ($query) => $query->active(), 'primaryImage']),
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
            ->whereHas('defaultVariant', fn ($query) => $query->active())
            ->with(['brand', 'categories', 'defaultVariant', 'primaryImage']);
    }

    private function homepageCategorySections()
    {
        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->whereHas('children', fn ($query) => $query->active())
            ->with(['children' => fn ($query) => $query->active()->orderBy('display_order')->orderBy('name')])
            ->orderBy('display_order')
            ->orderBy('name')
            ->take(9)
            ->get()
            ->values();
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

    private function homepagePartners(): array
    {
        return [
            ['name' => 'FreshFarm', 'description' => 'Organics', 'discount' => 'UPTO 15% OFF', 'class' => 'fresh'],
            ['name' => 'MilkyDay', 'description' => 'Dairy Products', 'discount' => 'UPTO 10% OFF', 'class' => 'dairy'],
            ['name' => 'DailyBasket', 'description' => 'Meat & Seafood', 'discount' => 'UPTO 12% OFF', 'class' => 'basket'],
            ['name' => 'PetWorld', 'description' => 'Pet Supplies', 'discount' => 'UPTO 8% OFF', 'class' => 'care'],
        ];
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
