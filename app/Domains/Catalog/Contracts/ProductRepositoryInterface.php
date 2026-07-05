<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Product;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 15);

    public function activeProducts();

    public function featuredProducts();

    public function findWithTrashed(int $id);

    public function findWithRelations(int $id, array $relations = []);

    public function syncCategories(Product $product, array $categoryPayload): void;

    public function bulkDelete(array $ids): int;

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkRestore(array $ids): int;

    public function idsInUse(array $ids): array;
}
