<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;

interface AttributeValueRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 15);

    public function activeValues();

    public function valuesForAttribute(int $attributeId);

    public function findWithTrashed(int $id);

    public function bulkDelete(array $ids): int;

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkRestore(array $ids): int;

    public function idsInUse(array $ids): array;

    public function idsWithInactiveAttributes(array $ids): array;

    public function activeIdsWithInactiveAttributes(array $ids): array;
}
