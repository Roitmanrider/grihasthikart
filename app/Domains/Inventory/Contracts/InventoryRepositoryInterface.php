<?php

namespace App\Domains\Inventory\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Inventory;

interface InventoryRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function findWithRelations(int $id): Inventory;

    public function findForVariantLocation(int $productVariantId, int $stockLocationId): ?Inventory;

    public function lockForVariantLocation(int $productVariantId, int $stockLocationId): ?Inventory;

    public function movementHistory(Inventory $inventory, int $perPage = 20);

    public function activeLocations();

    public function allLocations();

    public function activeVariants();

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkDelete(array $ids): int;

    public function bulkRestore(array $ids): int;
}
