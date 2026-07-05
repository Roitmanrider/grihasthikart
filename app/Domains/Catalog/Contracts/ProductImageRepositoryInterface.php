<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\ProductImage;

interface ProductImageRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 15);

    public function forProduct(int $productId, array $filters = []);

    public function forVariant(int $productVariantId, array $filters = []);

    public function findWithTrashed(int $id);

    public function clearPrimaryForProduct(int $productId, ?int $exceptImageId = null): int;

    public function clearPrimaryForVariant(int $productVariantId, ?int $exceptImageId = null): int;

    public function restore(ProductImage $image): bool;
}
