<?php

namespace App\Domains\Customer\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Customer;

interface CustomerRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function findWithTrashed(int $id): Customer;

    public function findByMobile(string $mobile): ?Customer;

    public function findWithDetails(int $id): Customer;

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkDelete(array $ids): int;

    public function bulkRestore(array $ids): int;
}
