<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;

interface BrandRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 15);

    public function activeBrands();

    public function featuredBrands();

    public function findWithTrashed(int $id);

    public function bulkDelete(array $ids): int;

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkRestore(array $ids): int;
}
