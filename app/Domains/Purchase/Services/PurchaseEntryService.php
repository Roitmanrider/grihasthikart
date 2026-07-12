<?php

namespace App\Domains\Purchase\Services;

use App\Domains\Inventory\Services\InventoryService;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\StockLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PurchaseEntryService
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    public function paginate(int $perPage = 20)
    {
        return PurchaseEntry::query()
            ->withCount('items')
            ->latest('purchase_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): PurchaseEntry
    {
        $items = $this->normalizedItems($data['items'] ?? []);
        $totals = $this->totals($items);

        return DB::transaction(function () use ($data, $items, $totals) {
            $purchase = PurchaseEntry::query()->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_number' => $this->generatePurchaseNumber(),
                'bill_number' => $data['bill_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $totals['subtotal'],
                'gst_total' => $totals['gst_total'],
                'discount_total' => 0,
                'grand_total' => $totals['grand_total'],
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseEntry::STATUS_POSTED,
            ]);

            foreach ($items as $item) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->findOrFail($item['product_variant_id']);

                $purchase->items()->create([
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'gst_rate' => $item['gst_rate'],
                    'gst_amount' => $item['gst_amount'],
                    'line_total' => $item['line_total'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $inventory = $this->inventoryForVariant($variant);
                $this->inventoryService->adjustStock(
                    $inventory,
                    'purchase',
                    $item['quantity'],
                    'Purchase '.$purchase->purchase_number,
                    PurchaseEntry::class,
                    $purchase->id
                );
            }

            return $purchase->fresh(['items.productVariant.product']);
        });
    }

    public function options(): array
    {
        $variants = ProductVariant::query()
            ->active()
            ->with(['product', 'inventories'])
            ->whereHas('product', fn ($query) => $query->active())
            ->orderBy('sku')
            ->get();

        return [
            'variants' => $variants,
        ];
    }

    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $quantity = (float) $item['quantity'];
                $purchasePrice = (float) $item['purchase_price'];
                $gstRate = (float) ($item['gst_rate'] ?? 0);
                $subtotal = round($quantity * $purchasePrice, 2);
                $gstAmount = round($subtotal * $gstRate / 100, 2);

                return [
                    'product_variant_id' => (int) $item['product_variant_id'],
                    'quantity' => $quantity,
                    'purchase_price' => $purchasePrice,
                    'gst_rate' => $gstRate,
                    'gst_amount' => $gstAmount,
                    'line_total' => round($subtotal + $gstAmount, 2),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function totals(array $items): array
    {
        $subtotal = round(collect($items)->sum(fn (array $item) => $item['quantity'] * $item['purchase_price']), 2);
        $gstTotal = round(collect($items)->sum('gst_amount'), 2);

        return [
            'subtotal' => $subtotal,
            'gst_total' => $gstTotal,
            'grand_total' => round($subtotal + $gstTotal, 2),
        ];
    }

    private function inventoryForVariant(ProductVariant $variant): Inventory
    {
        $location = StockLocation::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('id')
            ->first();

        if (! $location) {
            throw new InvalidArgumentException('Create an active stock location before posting purchases.');
        }

        /** @var Inventory $inventory */
        $inventory = Inventory::query()->firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'stock_location_id' => $location->id,
            ],
            [
                'quantity_on_hand' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'status' => true,
            ]
        );

        return $inventory;
    }

    private function generatePurchaseNumber(): string
    {
        do {
            $number = 'PUR'.now()->format('ymd').Str::upper(Str::random(5));
        } while (PurchaseEntry::query()->where('purchase_number', $number)->exists());

        return $number;
    }
}
