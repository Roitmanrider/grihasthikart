<?php

namespace App\Domains\Catalog\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\ProductVariant;

interface ProductVariantRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 15);

    public function forProduct(int $productId, array $filters = [], int $perPage = 15);

    public function activeForProduct(int $productId);

    public function findWithTrashed(int $id);

    public function findBySku(string $sku);

    public function findByBarcode(string $barcode);

    public function defaultForProduct(int $productId);

    public function defaultVariantsByIds(array $ids);

    public function existsCombination(int $productId, string $attributeSignature, ?int $ignoreId = null): bool;

    public function clearDefaultForProduct(int $productId, ?int $exceptVariantId = null): int;

    public function syncAttributeValues(ProductVariant $variant, array $attributePayload): void;

    public function countForProduct(int $productId): int;

    public function idsInUse(array $ids): array;

    public function idsBelongingToInactiveProducts(array $ids): array;

    public function idsWithInactiveAttributeData(array $ids): array;

    public function idsBlockingDefaultDelete(array $ids): array;

    public function idsNotBelongingToProduct(int $productId, array $ids): array;

    public function bulkDelete(array $ids): int;

    public function bulkUpdateStatus(array $ids, bool $status): int;

    public function bulkRestore(array $ids): int;
}
