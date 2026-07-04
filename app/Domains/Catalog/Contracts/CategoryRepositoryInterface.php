<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 15);

    public function parentOptions(?int $excludeId = null);

    public function rootCategories();

    public function activeCategories();

    public function featuredCategories();

    public function menuCategories();

    public function homepageCategories();

    public function findWithTrashed(int $id);

    public function bulkDelete(array $ids): int;

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkRestore(array $ids): int;

    public function idsWithChildren(array $ids): array;

    public function idsWithActiveChildren(array $ids): array;
}
