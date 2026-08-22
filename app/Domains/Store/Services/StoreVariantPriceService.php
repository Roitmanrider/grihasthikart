<?php

namespace App\Domains\Store\Services;

use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StoreVariantPrice;
use App\Models\StoreVariantPriceHistory;
use App\Models\User;

class StoreVariantPriceService
{
    public function effectivePrice(ProductVariant $variant, ?StockLocation $store = null): array
    {
        $storePrice = $store
            ? StoreVariantPrice::query()
                ->effective()
                ->where('stock_location_id', $store->id)
                ->where('product_variant_id', $variant->id)
                ->first()
            : null;

        return [
            'mrp' => (float) ($storePrice?->mrp ?? $variant->mrp),
            'selling_price' => (float) ($storePrice?->selling_price ?? $variant->selling_price),
            'source' => $storePrice ? 'store' : 'global',
            'store_price' => $storePrice,
        ];
    }

    public function updatePrice(StockLocation $store, ProductVariant $variant, array $data, ?User $user = null): StoreVariantPrice
    {
        $effectiveFrom = $data['effective_from'] ?? null;
        $effectiveUntil = $data['effective_until'] ?? null;

        if ($effectiveFrom && $effectiveUntil && strtotime((string) $effectiveUntil) < strtotime((string) $effectiveFrom)) {
            throw new \InvalidArgumentException('The effective-until time must be after the effective-from time.');
        }

        $existing = StoreVariantPrice::query()
            ->where('stock_location_id', $store->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $price = StoreVariantPrice::query()->updateOrCreate(
            [
                'stock_location_id' => $store->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'mrp' => $data['mrp'] ?? $existing?->mrp ?? $variant->mrp,
                'selling_price' => $data['selling_price'],
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'source' => $data['source'] ?? 'manual',
                'changed_by' => $user?->id,
                'status' => (bool) ($data['status'] ?? true),
            ]
        );

        StoreVariantPriceHistory::query()->create([
            'stock_location_id' => $store->id,
            'product_variant_id' => $variant->id,
            'old_mrp' => $existing?->mrp,
            'old_selling_price' => $existing?->selling_price,
            'new_mrp' => $price->mrp,
            'new_selling_price' => $price->selling_price,
            'change_reason' => $data['change_reason'] ?? null,
            'changed_by' => $user?->id,
            'changed_at' => now(config('app.timezone')),
        ]);

        return $price;
    }
}
