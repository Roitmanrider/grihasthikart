<?php

namespace App\Domains\Coupon\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Coupon;

interface CouponRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function findByCode(string $code): ?Coupon;

    public function findWithDetails(int $id): Coupon;
}
