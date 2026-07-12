<?php

namespace App\Domains\Inventory\Services;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockAdjustmentService
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    public function paginate(int $perPage = 20)
    {
        return StockAdjustment::query()
            ->with(['productVariant.product', 'inventory.stockLocation', 'creator'])
            ->latest('adjustment_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateVerifications(int $perPage = 20)
    {
        return StockAdjustment::query()
            ->with(['productVariant.product', 'inventory.stockLocation', 'creator'])
            ->where('adjustment_type', 'set')
            ->where('reason', 'physical_count_mismatch')
            ->latest('adjustment_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createAdjustment(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $variant = ProductVariant::query()->findOrFail($data['product_variant_id']);
            $inventory = $this->inventoryForVariant($variant, shouldCreate: $data['adjustment_type'] !== 'decrease');
            $before = $inventory ? (float) $inventory->quantity_on_hand : 0.0;
            $after = $this->afterQuantity($data['adjustment_type'], $before, (float) $data['quantity']);
            $movementQuantity = abs($after - $before);

            $adjustment = StockAdjustment::query()->create([
                'product_variant_id' => $variant->id,
                'inventory_id' => $inventory?->id,
                'adjustment_type' => $data['adjustment_type'],
                'quantity' => (float) $data['quantity'],
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'adjustment_date' => $data['adjustment_date'],
                'created_by' => Auth::id(),
            ]);

            if ($movementQuantity > 0) {
                $this->moveStock($inventory, $before, $after, $movementQuantity, $adjustment);
            }

            return $adjustment->fresh(['productVariant.product', 'inventory.stockLocation', 'creator']);
        });
    }

    public function createVerification(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $variant = ProductVariant::query()->findOrFail($data['product_variant_id']);
            $counted = (float) $data['counted_stock'];
            $inventory = $this->inventoryForVariant($variant, shouldCreate: $counted > 0);
            $before = $inventory ? (float) $inventory->quantity_on_hand : 0.0;
            $difference = $counted - $before;

            $adjustment = StockAdjustment::query()->create([
                'product_variant_id' => $variant->id,
                'inventory_id' => $inventory?->id,
                'adjustment_type' => 'set',
                'quantity' => abs($difference),
                'before_quantity' => $before,
                'after_quantity' => $counted,
                'reason' => 'physical_count_mismatch',
                'notes' => $data['notes'] ?? ($difference == 0.0 ? 'Physical count matched system stock.' : 'Physical verification adjusted stock.'),
                'reference_number' => $data['reference_number'] ?? null,
                'adjustment_date' => $data['verification_date'],
                'created_by' => Auth::id(),
            ]);

            if (abs($difference) > 0) {
                $this->moveStock($inventory, $before, $counted, abs($difference), $adjustment);
            }

            return $adjustment->fresh(['productVariant.product', 'inventory.stockLocation', 'creator']);
        });
    }

    public function options(): array
    {
        return [
            'variants' => ProductVariant::query()
                ->active()
                ->with(['product', 'inventories.stockLocation'])
                ->whereHas('product', fn ($query) => $query->active())
                ->orderBy('sku')
                ->get(),
            'reasons' => StockAdjustment::REASONS,
        ];
    }

    private function afterQuantity(string $type, float $before, float $quantity): float
    {
        $after = match ($type) {
            'increase' => $before + $quantity,
            'decrease' => $before - $quantity,
            'set' => $quantity,
            default => throw new InvalidArgumentException('Invalid adjustment type.'),
        };

        if ($after < 0) {
            throw new InvalidArgumentException('Stock adjustment cannot make stock negative.');
        }

        return $after;
    }

    private function moveStock(Inventory $inventory, float $before, float $after, float $quantity, StockAdjustment $adjustment): void
    {
        $movementType = $after > $before ? 'adjustment_in' : 'adjustment_out';

        $this->inventoryService->adjustStock(
            $inventory,
            $movementType,
            $quantity,
            $this->movementNote($adjustment),
            StockAdjustment::class,
            $adjustment->id
        );
    }

    private function movementNote(StockAdjustment $adjustment): string
    {
        $reason = str($adjustment->reason)->replace('_', ' ')->headline()->toString();

        return 'Stock adjustment '.$adjustment->id.' - '.$reason;
    }

    private function inventoryForVariant(ProductVariant $variant, bool $shouldCreate): ?Inventory
    {
        $location = StockLocation::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('id')
            ->first();

        if (! $location) {
            throw new InvalidArgumentException('Create an active stock location before adjusting stock.');
        }

        if (! $shouldCreate) {
            return Inventory::query()
                ->where('product_variant_id', $variant->id)
                ->where('stock_location_id', $location->id)
                ->first();
        }

        return Inventory::query()->firstOrCreate(
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
    }
}
