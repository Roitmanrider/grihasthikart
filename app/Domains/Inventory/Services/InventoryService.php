<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Contracts\InventoryRepositoryInterface;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    private const INCREASE_TYPES = ['opening', 'purchase', 'adjustment_in', 'return_in', 'cancellation_return'];

    private const DECREASE_TYPES = ['adjustment_out', 'sale'];

    public function __construct(
        private readonly InventoryRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function options(): array
    {
        return [
            'variants' => $this->repository->activeVariants(),
            'locations' => $this->repository->activeLocations(),
        ];
    }

    public function createInventory(ProductVariant $variant, StockLocation $location, array $data): Inventory
    {
        $this->ensureLocationIsActive($location);

        return DB::transaction(function () use ($variant, $location, $data) {
            $this->ensureSingleDefaultLocation($location);

            try {
                /** @var Inventory $inventory */
                $inventory = $this->repository->create([
                    'product_variant_id' => $variant->id,
                    'stock_location_id' => $location->id,
                    'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
                    'reserved_quantity' => $data['reserved_quantity'] ?? 0,
                    'damaged_quantity' => $data['damaged_quantity'] ?? 0,
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? null,
                    'reorder_level' => $data['reorder_level'] ?? null,
                    'target_stock_level' => $data['target_stock_level'] ?? null,
                    'status' => (bool) ($data['status'] ?? true),
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueConstraintViolation($exception)) {
                    throw new InvalidArgumentException('Inventory already exists for this variant and stock location.');
                }

                throw $exception;
            }

            $this->ensureNonNegativeQuantities($inventory);

            if ((float) $inventory->quantity_on_hand > 0) {
                $this->writeMovement($inventory, 'opening', (float) $inventory->quantity_on_hand, 'Opening stock');
            }

            return $inventory;
        });
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        return DB::transaction(function () use ($inventory, $data) {
            /** @var Inventory $lockedInventory */
            $lockedInventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();

            $updated = $this->repository->update($lockedInventory, [
                'low_stock_threshold' => $data['low_stock_threshold'] ?? null,
                'reorder_level' => $data['reorder_level'] ?? null,
                'target_stock_level' => $data['target_stock_level'] ?? null,
                'status' => (bool) ($data['status'] ?? $lockedInventory->status),
            ]);

            return $updated->fresh(['productVariant.product', 'stockLocation']);
        });
    }

    public function adjustStock(Inventory $inventory, string $movementType, float $quantity, ?string $note = null): Inventory
    {
        if (! in_array($movementType, InventoryMovement::TYPES, true)) {
            throw new InvalidArgumentException('Invalid inventory movement type.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Adjustment quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($inventory, $movementType, $quantity, $note) {
            /** @var Inventory $lockedInventory */
            $lockedInventory = Inventory::query()
                ->whereKey($inventory->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureLocationIsActive($lockedInventory->stockLocation);

            if ($movementType === 'reservation') {
                return $this->reserveStock($lockedInventory->product_variant_id, $lockedInventory->stock_location_id, $quantity, $note);
            }

            if ($movementType === 'reservation_release') {
                return $this->releaseReservedStock($lockedInventory->product_variant_id, $lockedInventory->stock_location_id, $quantity, $note);
            }

            match (true) {
                in_array($movementType, self::INCREASE_TYPES, true) => $this->increaseStock($lockedInventory, $quantity),
                in_array($movementType, self::DECREASE_TYPES, true) => $this->decreaseStock($lockedInventory, $quantity),
                $movementType === 'damaged' => $this->markDamaged($lockedInventory, $quantity),
                default => throw new InvalidArgumentException('Unsupported movement type for manual adjustment.'),
            };

            $lockedInventory->refresh();
            $this->writeMovement($lockedInventory, $movementType, $quantity, $note);

            return $lockedInventory->fresh(['productVariant.product', 'stockLocation']);
        });
    }

    public function increaseStock(Inventory $inventory, float $quantity): void
    {
        $inventory->increment('quantity_on_hand', $quantity);
    }

    public function decreaseStock(Inventory $inventory, float $quantity): void
    {
        if ((float) $inventory->quantity_on_hand - $quantity < 0) {
            throw new InvalidArgumentException('Stock cannot go negative.');
        }

        $inventory->decrement('quantity_on_hand', $quantity);
    }

    public function markDamaged(Inventory $inventory, float $quantity): void
    {
        $this->ensureSufficientAvailableStock($inventory, $quantity);
        $inventory->increment('damaged_quantity', $quantity);
    }

    public function reserveStock(int $productVariantId, int $stockLocationId, float $quantity, ?string $note = null): Inventory
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($productVariantId, $stockLocationId, $quantity, $note) {
            /** @var Inventory $inventory */
            $inventory = $this->repository->lockForVariantLocation($productVariantId, $stockLocationId);

            if (! $inventory) {
                throw new InvalidArgumentException('Inventory record not found for reservation.');
            }

            $this->ensureLocationIsActive($inventory->stockLocation);
            $this->ensureSufficientAvailableStock($inventory, $quantity);
            $inventory->increment('reserved_quantity', $quantity);
            $inventory->refresh();
            $this->writeMovement($inventory, 'reservation', $quantity, $note);

            return $inventory->fresh(['productVariant.product', 'stockLocation']);
        });
    }

    public function releaseReservedStock(int $productVariantId, int $stockLocationId, float $quantity, ?string $note = null): Inventory
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Release quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($productVariantId, $stockLocationId, $quantity, $note) {
            /** @var Inventory $inventory */
            $inventory = $this->repository->lockForVariantLocation($productVariantId, $stockLocationId);

            if (! $inventory) {
                throw new InvalidArgumentException('Inventory record not found for reservation release.');
            }

            if ((float) $inventory->reserved_quantity - $quantity < 0) {
                throw new InvalidArgumentException('Reserved quantity cannot go negative.');
            }

            $inventory->decrement('reserved_quantity', $quantity);
            $inventory->refresh();
            $this->writeMovement($inventory, 'reservation_release', $quantity, $note);

            return $inventory->fresh(['productVariant.product', 'stockLocation']);
        });
    }

    public function getAvailableQuantity(int $productVariantId, ?int $stockLocationId = null): float
    {
        $query = Inventory::query()
            ->active()
            ->where('product_variant_id', $productVariantId);

        if ($stockLocationId !== null) {
            $query->where('stock_location_id', $stockLocationId);
        }

        return (float) $query->get()->sum(fn (Inventory $inventory) => $inventory->available_quantity);
    }

    public function ensureSufficientAvailableStock(Inventory $inventory, float $quantity): void
    {
        if ($inventory->available_quantity - $quantity < 0) {
            throw new InvalidArgumentException('Insufficient available stock.');
        }
    }

    public function ensureLocationIsActive(StockLocation $location): void
    {
        if (! $location->status || $location->trashed()) {
            throw new InvalidArgumentException('Inactive stock locations cannot be used for stock changes.');
        }
    }

    public function delete(Inventory $inventory): bool
    {
        return $this->repository->delete($inventory);
    }

    public function restore(int $id): Inventory
    {
        $inventory = Inventory::withTrashed()->findOrFail($id);
        $inventory->restore();

        return $inventory;
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->repository->bulkRestore($ids);
    }

    private function ensureNonNegativeQuantities(Inventory $inventory): void
    {
        if ($inventory->available_quantity < 0) {
            throw new InvalidArgumentException('Reserved and damaged quantities cannot exceed stock on hand.');
        }
    }

    private function ensureSingleDefaultLocation(StockLocation $location): void
    {
        if (! $location->is_default) {
            return;
        }

        StockLocation::query()
            ->whereKeyNot($location->id)
            ->update(['is_default' => false]);
    }

    private function writeMovement(Inventory $inventory, string $movementType, float $quantity, ?string $note = null): InventoryMovement
    {
        return InventoryMovement::query()->create([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'stock_location_id' => $inventory->stock_location_id,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'balance_after' => $inventory->quantity_on_hand,
            'note' => $note,
            'created_by' => Auth::id(),
        ]);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'inventories_product_variant_id_stock_location_id_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
