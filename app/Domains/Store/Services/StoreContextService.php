<?php

namespace App\Domains\Store\Services;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\StockLocation;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Str;

class StoreContextService
{
    public function resolveForSessionIdentifier(string $sessionId): ?StockLocation
    {
        $customer = $this->customerFromSessionIdentifier($sessionId);

        return $this->resolve($customer);
    }

    public function resolve(?Customer $customer = null, ?Cart $cart = null): ?StockLocation
    {
        if ($customer?->assignedStore && $this->isUsable($customer->assignedStore)) {
            return $customer->assignedStore;
        }

        if ($cart?->stockLocation && $this->isUsable($cart->stockLocation)) {
            return $cart->stockLocation;
        }

        return $this->defaultStore();
    }

    public function resolveFromSession(SessionStore $session): ?StockLocation
    {
        if ($session->has('customer_id')) {
            $customer = Customer::query()->with('assignedStore')->find((int) $session->get('customer_id'));

            return $this->resolve($customer);
        }

        return $this->defaultStore();
    }

    public function defaultStore(): ?StockLocation
    {
        return StockLocation::query()
            ->active()
            ->where('accepts_online_orders', true)
            ->where('is_default', true)
            ->orderBy('id')
            ->first();
    }

    private function customerFromSessionIdentifier(string $sessionId): ?Customer
    {
        if (! str_starts_with($sessionId, 'customer:')) {
            return null;
        }

        $customerId = (int) Str::after($sessionId, 'customer:');

        return $customerId > 0
            ? Customer::query()->with('assignedStore')->find($customerId)
            : null;
    }

    private function isUsable(StockLocation $store): bool
    {
        return (bool) $store->status && (bool) $store->accepts_online_orders && $store->deleted_at === null;
    }
}
