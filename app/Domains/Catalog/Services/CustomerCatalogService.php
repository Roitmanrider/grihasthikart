<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Storefront\Services\HomepageContentService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $filters = $this->normalizeFilters($filters);
        $query = $this->filteredProductsQuery($filters);

        foreach (['is_featured', 'is_new_arrival', 'is_popular', 'is_trending'] as $flag) {
            if (($filters[$flag] ?? null) === '1') {
                $query->where($flag, true);
            }
        }

        $this->applySort($query, $filters['sort'], $filters['q']);

        return $query->paginate($perPage)->withQueryString();
    }

    public function productSearch(array $filters = [], int $perPage = 12): array
    {
        $filters = $this->normalizeFilters($filters);

        if ($filters['q'] === '' && ! $this->hasActiveFilters($filters)) {
            return [
                'products' => $this->customerProductsQuery()->whereRaw('1 = 0')->paginate($perPage)->withQueryString(),
                'filters' => $filters,
                'categorySuggestions' => collect(),
                'filterOptions' => $this->filterOptions($filters),
            ];
        }

        $query = $this->filteredProductsQuery($filters);
        $this->applySort($query, $filters['sort'], $filters['q']);

        return [
            'products' => $query->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'categorySuggestions' => $this->categorySuggestions($filters['q']),
            'filterOptions' => $this->filterOptions($filters),
        ];
    }

    public function listingMeta(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return [
            'filters' => $filters,
            'categorySuggestions' => $this->categorySuggestions($filters['q']),
            'filterOptions' => $this->filterOptions($filters),
        ];
    }

    public function autocomplete(string $query, int $limit = 10): array
    {
        $term = $this->normalizeSearchTerm($query);

        if (Str::length($term) < 2) {
            return [];
        }

        $limit = max(1, min($limit, 12));
        $categoryLimit = min(4, $limit);
        $brandLimit = min(3, $limit);
        $productLimit = $limit;

        $categories = Category::query()
            ->active()
            ->where(fn ($query) => $this->applyTextMatch($query, $term, ['name', 'slug', 'meta_keywords']))
            ->orderByRaw($this->textRankSql('categories.name'), $this->textRankBindings($term))
            ->orderBy('display_order')
            ->orderBy('name')
            ->limit($categoryLimit)
            ->get()
            ->map(fn (Category $category) => [
                'type' => 'category',
                'label' => $category->name,
                'url' => route('categories.show', $category->slug),
                'meta' => $category->parent_id ? 'Category' : 'Root category',
            ]);

        $brands = Brand::query()
            ->active()
            ->where(fn ($query) => $this->applyTextMatch($query, $term, ['name', 'slug']))
            ->orderByRaw($this->textRankSql('brands.name'), $this->textRankBindings($term))
            ->orderBy('display_order')
            ->orderBy('name')
            ->limit($brandLimit)
            ->get()
            ->map(fn (Brand $brand) => [
                'type' => 'brand',
                'label' => $brand->name,
                'url' => route('brands.show', $brand->slug),
                'meta' => 'Brand',
            ]);

        $products = $this->customerProductsQuery()
            ->where(fn ($query) => $this->applySearch($query, $term))
            ->orderByRaw($this->relevanceSql(), $this->relevanceBindings($term))
            ->orderBy('products.name')
            ->limit($productLimit)
            ->get()
            ->map(fn (Product $product) => [
                'type' => 'product',
                'label' => $product->name,
                'url' => route('products.index', ['q' => $product->name]),
                'meta' => $product->brand?->name ?? 'Product',
            ]);

        return $categories
            ->concat($brands)
            ->concat($products)
            ->take($limit)
            ->values()
            ->all();
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
        $filters = $this->normalizeFilters($filters);
        $filters['category_ids'] = $categoryIds;
        $query = $this->filteredProductsQuery($filters);

        $this->applySort($query, $filters['sort'], $filters['q'], false);

        return [
            'category' => $category,
            'products' => $query->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($filters),
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

        $filters = $this->normalizeFilters($filters);
        $filters['brand_ids'] = [$brand->id];
        $query = $this->filteredProductsQuery($filters);

        $this->applySort($query, $filters['sort'], $filters['q'], false);

        return [
            'brand' => $brand,
            'products' => $query->paginate($perPage)->withQueryString(),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($filters),
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
            ->whereHas('brand', fn ($query) => $query->active())
            ->whereHas('variants', fn ($query) => $query->active())
            ->with([
                'brand',
                'categories.parent',
                'variants' => fn ($query) => $query->active()->with(['inventories', 'dailyOffers.cartItems.cart', 'dailyOffers.orderItems', 'primaryImage']),
                'defaultVariant.primaryImage',
                'primaryImage',
            ]);
    }

    private function filteredProductsQuery(array $filters): Builder
    {
        $query = $this->customerProductsQuery()
            ->select('products.*');

        if ($filters['q'] !== '') {
            $query->where(fn ($query) => $this->applySearch($query, $filters['q']));
        }

        if (! empty($filters['category_ids'])) {
            $query->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $filters['category_ids']));
        } elseif ($filters['category_id']) {
            $query->whereHas('categories', fn ($query) => $query->whereKey($filters['category_id']));
        }

        if ($filters['brand_ids'] !== []) {
            $query->whereIn('brand_id', $filters['brand_ids']);
        }

        if ($filters['min_price'] !== null) {
            $query->whereHas('variants', fn ($query) => $query->active()->where('selling_price', '>=', $filters['min_price']));
        }

        if ($filters['max_price'] !== null) {
            $query->whereHas('variants', fn ($query) => $query->active()->where('selling_price', '<=', $filters['max_price']));
        }

        if ($filters['weights'] !== []) {
            $query->whereHas('variants', function ($query) use ($filters) {
                $query->active()->whereIn('variant_name', $filters['weights']);
            });
        }

        if ($filters['discount_min'] !== null) {
            $threshold = $filters['discount_min'];
            $query->whereHas('variants', function ($query) use ($threshold) {
                $query->active()
                    ->where('mrp', '>', 0)
                    ->whereColumn('mrp', '>', 'selling_price')
                    ->whereRaw('((mrp - selling_price) * 100.0 / mrp) >= ?', [$threshold]);
            });
        }

        return $query;
    }

    private function applySearch(Builder $query, string $term): void
    {
        $like = $this->likeTerm($term);
        $compact = $this->likeTerm(str_replace(' ', '', $term));

        $query->where('products.name', 'like', $like)
            ->orWhere('products.slug', 'like', $like)
            ->orWhere('products.short_description', 'like', $like)
            ->orWhere('products.meta_keywords', 'like', $like)
            ->orWhereHas('brand', fn ($query) => $this->applyTextMatch($query, $term, ['name', 'slug']))
            ->orWhereHas('categories', fn ($query) => $this->applyTextMatch($query, $term, ['name', 'slug', 'meta_keywords']))
            ->orWhereHas('variants', function ($query) use ($like, $compact) {
                $query->active()
                    ->where(function ($query) use ($like, $compact) {
                        $query->where('variant_name', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhereRaw("REPLACE(CONCAT(COALESCE(weight, ''), COALESCE(unit, '')), ' ', '') LIKE ?", [$compact])
                            ->orWhereHas('attributeValues', fn ($query) => $query->where('value', 'like', $like));
                    });
            });
    }

    private function applyTextMatch(Builder $query, string $term, array $columns): void
    {
        $like = $this->likeTerm($term);

        foreach ($columns as $index => $column) {
            $method = $index === 0 ? 'where' : 'orWhere';
            $query->{$method}($column, 'like', $like);
        }
    }

    private function applySort(Builder $query, string $sort, string $term = '', bool $allowRelevance = true): void
    {
        match ($sort) {
            'relevance' => $allowRelevance && $term !== ''
                ? $query->orderByRaw($this->relevanceSql(), $this->relevanceBindings($term))->orderBy('products.name')
                : $query->latest('products.created_at')->orderBy('products.id'),
            'name' => $query->orderBy('products.name')->orderBy('products.id'),
            'price_asc' => $query->orderBy($this->minPriceSubquery())->orderBy('products.name'),
            'price_desc' => $query->orderByDesc($this->minPriceSubquery())->orderBy('products.name'),
            'discount_desc' => $query->orderByDesc($this->discountSubquery())->orderBy('products.name'),
            default => $query->latest('products.created_at'),
        };
    }

    private function normalizeFilters(array $filters): array
    {
        $term = $this->normalizeSearchTerm((string) ($filters['q'] ?? $filters['search'] ?? ''));
        $brandIds = collect(Arr::wrap($filters['brand'] ?? $filters['brands'] ?? []))
            ->merge(Arr::wrap($filters['brand_ids'] ?? []))
            ->filter(fn ($value) => is_numeric($value) && (int) $value > 0)
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $minPrice = $this->positiveDecimal($filters['min_price'] ?? null);
        $maxPrice = $this->positiveDecimal($filters['max_price'] ?? null);

        if ($minPrice !== null && $maxPrice !== null && $maxPrice < $minPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $sort = in_array($filters['sort'] ?? null, ['relevance', 'latest', 'name', 'price_asc', 'price_desc', 'discount_desc'], true)
            ? $filters['sort']
            : ($term !== '' ? 'relevance' : 'latest');

        return array_merge($filters, [
            'q' => Str::limit($term, 150, ''),
            'category_id' => is_numeric($filters['category'] ?? null) ? (int) $filters['category'] : null,
            'category_ids' => collect($filters['category_ids'] ?? [])->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (int) $value)->values()->all(),
            'brand_ids' => $brandIds,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'weights' => collect(Arr::wrap($filters['weight'] ?? $filters['weights'] ?? []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->take(20)
                ->values()
                ->all(),
            'discount_min' => in_array((int) ($filters['discount_min'] ?? 0), [10, 20, 30], true) ? (int) $filters['discount_min'] : null,
            'sort' => $sort,
        ]);
    }

    private function filterOptions(array $filters): array
    {
        $base = $this->customerProductsQuery();

        if ($filters['q'] !== '') {
            $base->where(fn ($query) => $this->applySearch($query, $filters['q']));
        }

        if (! empty($filters['category_ids'])) {
            $base->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $filters['category_ids']));
        } elseif ($filters['category_id']) {
            $base->whereHas('categories', fn ($query) => $query->whereKey($filters['category_id']));
        }

        $productIds = (clone $base)->pluck('products.id');

        return [
            'brands' => Brand::query()
                ->active()
                ->whereHas('products', fn ($query) => $query->whereIn('products.id', $productIds))
                ->orderBy('name')
                ->get(),
            'weights' => ProductVariant::query()
                ->active()
                ->whereIn('product_id', $productIds)
                ->whereNotNull('variant_name')
                ->select('variant_name')
                ->distinct()
                ->orderBy('variant_name')
                ->limit(40)
                ->pluck('variant_name'),
            'discounts' => [10, 20, 30],
        ];
    }

    private function categorySuggestions(string $term): Collection
    {
        if ($term === '') {
            return collect();
        }

        return Category::query()
            ->active()
            ->where(fn ($query) => $this->applyTextMatch($query, $term, ['name', 'slug', 'meta_keywords']))
            ->with('parent')
            ->orderByRaw($this->textRankSql('categories.name'), $this->textRankBindings($term))
            ->orderBy('display_order')
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    private function hasActiveFilters(array $filters): bool
    {
        return $filters['brand_ids'] !== []
            || $filters['min_price'] !== null
            || $filters['max_price'] !== null
            || $filters['weights'] !== []
            || $filters['discount_min'] !== null
            || $filters['category_id'] !== null
            || $filters['category_ids'] !== [];
    }

    private function normalizeSearchTerm(string $term): string
    {
        $term = preg_replace('/[^\pL\pN\s.\-]/u', ' ', $term) ?: '';
        $term = preg_replace('/\s+/u', ' ', trim($term)) ?: '';

        return Str::lower($term);
    }

    private function positiveDecimal(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max((float) $value, 0);
    }

    private function likeTerm(string $term): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
    }

    private function prefixTerm(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
    }

    private function textRankSql(string $column): string
    {
        return "CASE WHEN LOWER({$column}) = ? THEN 1 WHEN LOWER({$column}) LIKE ? THEN 2 ELSE 3 END";
    }

    private function textRankBindings(string $term): array
    {
        return [$term, $this->prefixTerm($term)];
    }

    private function relevanceSql(): string
    {
        return <<<'SQL'
CASE
    WHEN LOWER(products.name) = ? THEN 100
    WHEN LOWER(products.name) LIKE ? THEN 90
    WHEN LOWER(products.name) LIKE ? THEN 80
    WHEN EXISTS (
        SELECT 1 FROM category_product
        INNER JOIN categories ON categories.id = category_product.category_id
        WHERE category_product.product_id = products.id
        AND categories.deleted_at IS NULL
        AND categories.status = 1
        AND LOWER(categories.name) = ?
    ) THEN 70
    WHEN EXISTS (
        SELECT 1 FROM brands
        WHERE brands.id = products.brand_id
        AND brands.deleted_at IS NULL
        AND brands.status = 1
        AND LOWER(brands.name) LIKE ?
    ) THEN 60
    WHEN EXISTS (
        SELECT 1 FROM product_variants
        WHERE product_variants.product_id = products.id
        AND product_variants.deleted_at IS NULL
        AND product_variants.status = 1
        AND (
            LOWER(product_variants.variant_name) LIKE ?
            OR LOWER(product_variants.sku) LIKE ?
            OR REPLACE(CONCAT(COALESCE(product_variants.weight, ''), COALESCE(product_variants.unit, '')), ' ', '') LIKE ?
        )
    ) THEN 50
    ELSE 10
END DESC
SQL;
    }

    private function relevanceBindings(string $term): array
    {
        return [
            $term,
            $this->prefixTerm($term),
            $this->likeTerm($term),
            $term,
            $this->likeTerm($term),
            $this->likeTerm($term),
            $this->likeTerm($term),
            $this->likeTerm(str_replace(' ', '', $term)),
        ];
    }

    private function minPriceSubquery()
    {
        return ProductVariant::query()
            ->selectRaw('MIN(selling_price)')
            ->whereColumn('product_id', 'products.id')
            ->where('status', true)
            ->whereNull('deleted_at');
    }

    private function discountSubquery()
    {
        return ProductVariant::query()
            ->selectRaw('MAX(CASE WHEN mrp > 0 AND mrp > selling_price THEN ((mrp - selling_price) * 100.0 / mrp) ELSE 0 END)')
            ->whereColumn('product_id', 'products.id')
            ->where('status', true)
            ->whereNull('deleted_at');
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
