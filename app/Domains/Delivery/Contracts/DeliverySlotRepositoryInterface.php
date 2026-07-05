<?php

namespace App\Domains\Delivery\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\DeliverySlot;

interface DeliverySlotRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function activeSlots();

    public function findWithTrashed(int $id): DeliverySlot;

    public function hasOverlap(string $startTime, string $endTime, ?int $ignoreId = null): bool;

    public function bulkRestore(array $ids): int;
}
