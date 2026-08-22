<?php

namespace App\Domains\Customer\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Cart\Services\PendingOrderService;
use App\Domains\Customer\Contracts\CustomerRepositoryInterface;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Store\Services\StoreVariantPriceService;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\DailyOffer;
use App\Models\PendingOrder;
use App\Models\StockLocation;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository,
        private readonly PendingOrderService $pendingOrderService,
        private readonly InventoryService $inventoryService,
        private readonly StoreVariantPriceService $storeVariantPriceService
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function create(array $data): Customer
    {
        return $this->repository->create($this->normalize($data));
    }

    public function update(Customer $customer, array $data): Customer
    {
        $data = $this->normalize($data);

        if (array_key_exists('assigned_store_id', $data) && (int) $customer->assigned_store_id !== (int) $data['assigned_store_id']) {
            return $this->reassignStore($customer, $data);
        }

        return $this->repository->update($customer, $data);
    }

    public function delete(Customer $customer): bool
    {
        return $this->repository->delete($customer);
    }

    public function restore(int $id): Customer
    {
        $customer = $this->repository->findWithTrashed($id);
        $customer->restore();

        return $customer;
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

    private function normalize(array $data): array
    {
        foreach (['status', 'is_premium', 'cashback_enabled', 'custom_delivery_rules_enabled'] as $flag) {
            $data[$flag] = (bool) ($data[$flag] ?? false);
        }

        foreach (['minimum_order_amount_override', 'delivery_charge_override', 'free_delivery_threshold_override'] as $field) {
            $data[$field] = ($data[$field] ?? null) === '' ? null : ($data[$field] ?? null);
        }

        if (array_key_exists('assigned_store_id', $data)) {
            $data['assigned_store_id'] = ($data['assigned_store_id'] ?? null) === '' ? null : ($data['assigned_store_id'] ?? null);
        }

        return $data;
    }

    private function reassignStore(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $newStore = ! empty($data['assigned_store_id'])
                ? StockLocation::query()->active()->findOrFail((int) $data['assigned_store_id'])
                : null;

            $activeCarts = $customer->carts()
                ->active()
                ->with(['items.productVariant.product', 'items.dailyOffer'])
                ->lockForUpdate()
                ->get();

            foreach ($activeCarts as $cart) {
                $pending = PendingOrder::query()
                    ->active()
                    ->where('cart_id', $cart->id)
                    ->lockForUpdate()
                    ->first();

                if ($pending) {
                    $this->pendingOrderService->releaseReservedStock($pending, 'Customer serving store changed');
                    $this->pendingOrderService->close($pending, 'STORE_REASSIGNED');
                }
            }

            $updated = $this->repository->update($customer, $data);

            foreach ($activeCarts as $cart) {
                $cart->forceFill(['stock_location_id' => $newStore?->id])->save();
                $this->revalidateCartForStore($cart->fresh(['items.productVariant.product', 'items.dailyOffer']), $newStore);

                if ($cart->fresh()->items()->exists()) {
                    $this->pendingOrderService->afterItemAddedOrUpdated($cart->fresh('items'));
                }
            }

            return $updated->fresh(['assignedStore']);
        });
    }

    private function revalidateCartForStore($cart, ?StockLocation $store): void
    {
        foreach ($cart->items as $item) {
            $variant = $item->productVariant;

            if (! $variant || ! $variant->status || ! $variant->product?->status) {
                $item->delete();

                continue;
            }

            if (! $this->cartItemValidForStore($item, $store)) {
                $item->delete();

                continue;
            }

            if ($item->sale_type === CartService::SALE_TYPE_NORMAL) {
                $price = $this->storeVariantPriceService->effectivePrice($variant, $store);
                $item->update([
                    'unit_price' => $price['selling_price'],
                    'mrp' => $price['mrp'],
                ]);
            }
        }
    }

    private function cartItemValidForStore(CartItem $item, ?StockLocation $store): bool
    {
        $storeId = $store?->id;

        if ($item->sale_type === CartService::SALE_TYPE_DAILY_OFFER) {
            $offer = DailyOffer::query()
                ->current()
                ->whereKey($item->daily_offer_id)
                ->where(function ($query) use ($storeId) {
                    $query->whereNull('stock_location_id')
                        ->when($storeId !== null, fn ($query) => $query->orWhere('stock_location_id', $storeId));
                })
                ->with(['cartItems.cart', 'orderItems'])
                ->first();

            return $offer !== null && $offer->availableOfferQuantity() + (float) $item->quantity >= (float) $item->quantity;
        }

        return $this->inventoryService->getAvailableQuantity((int) $item->product_variant_id, $storeId) >= (float) $item->quantity;
    }
}
