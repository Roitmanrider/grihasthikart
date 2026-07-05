<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Services\SlugService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
        private readonly SlugService $slugService
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function activeProducts()
    {
        return $this->repository->activeProducts();
    }

    public function featuredProducts()
    {
        return $this->repository->featuredProducts();
    }

    public function create(array $data)
    {
        $this->ensureCategoryRules($data);

        return $this->persistWithUniqueSlug(function (array $preparedData) use ($data) {
            return DB::transaction(function () use ($preparedData, $data) {
                /** @var Product $product */
                $product = $this->repository->create($preparedData);
                $this->syncCategories($product, $data['category_ids'], (int) $data['primary_category_id']);

                return $product;
            });
        }, $data);
    }

    public function update(Product $product, array $data)
    {
        $this->ensureCategoryRules($data);

        return $this->persistWithUniqueSlug(function (array $preparedData) use ($product, $data) {
            return DB::transaction(function () use ($product, $preparedData, $data) {
                /** @var Product $updatedProduct */
                $updatedProduct = $this->repository->update($product, $preparedData);
                $this->syncCategories($updatedProduct, $data['category_ids'], (int) $data['primary_category_id']);

                return $updatedProduct;
            });
        }, $data, $product);
    }

    public function delete(Product $product)
    {
        $this->ensureProductsCanBeDeleted([$product->id]);

        return $this->repository->delete($product);
    }

    public function restore(int $id)
    {
        $product = $this->repository->findWithTrashed($id);

        if (! $product->trashed()) {
            return $product;
        }

        $product->restore();

        return $product;
    }

    public function bulkDelete(array $ids): int
    {
        $this->ensureProductsCanBeDeleted($ids);

        return $this->repository->bulkDelete($ids);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->repository->bulkRestore($ids);
    }

    private function persistWithUniqueSlug(callable $operation, array $data, ?Product $product = null)
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $preparedData = $this->prepareData($data, $product, $attempt);

            try {
                return $operation($preparedData);
            } catch (QueryException $exception) {
                if (! $this->isUniqueSlugViolation($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to persist product with a unique slug.');
    }

    private function prepareData(array $data, ?Product $product = null, int $attempt = 0): array
    {
        $slugSource = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['name'];

        $data['slug'] = $this->slugService->generate(
            $slugSource,
            Product::class,
            $product?->id,
            'slug',
            $attempt
        );

        $data['brand_id'] = $data['brand_id'] ?? null;
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['minimum_order_quantity'] = $data['minimum_order_quantity'] ?? 1;
        $data['maximum_order_quantity'] = $data['maximum_order_quantity'] ?? null;
        $data['returnable'] = (bool) ($data['returnable'] ?? true);
        $data['cod_available'] = (bool) ($data['cod_available'] ?? true);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['is_trending'] = (bool) ($data['is_trending'] ?? false);
        $data['is_popular'] = (bool) ($data['is_popular'] ?? false);
        $data['is_new_arrival'] = (bool) ($data['is_new_arrival'] ?? false);
        $data['status'] = (bool) ($data['status'] ?? true);

        unset($data['category_ids'], $data['primary_category_id']);

        return $data;
    }

    private function syncCategories(Product $product, array $categoryIds, int $primaryCategoryId): void
    {
        $payload = [];

        foreach (array_values(array_unique(array_map('intval', $categoryIds))) as $index => $categoryId) {
            $payload[$categoryId] = [
                'is_primary' => $categoryId === $primaryCategoryId,
                'display_order' => $index,
            ];
        }

        $this->repository->syncCategories($product, $payload);
    }

    private function ensureCategoryRules(array $data): void
    {
        $categoryIds = array_values(array_unique(array_map('intval', $data['category_ids'] ?? [])));
        $primaryCategoryId = (int) ($data['primary_category_id'] ?? 0);

        if ($categoryIds === []) {
            throw new InvalidArgumentException('A product must belong to at least one category.');
        }

        if ($primaryCategoryId <= 0) {
            throw new InvalidArgumentException('A product must have exactly one primary category.');
        }

        if (! in_array($primaryCategoryId, $categoryIds, true)) {
            throw new InvalidArgumentException('The primary category must be one of the selected categories.');
        }
    }

    private function ensureProductsCanBeDeleted(array $ids): void
    {
        if ($this->repository->idsInUse($ids) !== []) {
            throw new InvalidArgumentException('Products linked to transactional records cannot be deleted.');
        }
    }

    private function isUniqueSlugViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'products_slug_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: products.slug')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
