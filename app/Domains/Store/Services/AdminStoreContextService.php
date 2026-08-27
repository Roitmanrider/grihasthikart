<?php

namespace App\Domains\Store\Services;

use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStoreContextService
{
    public const SESSION_KEY = 'admin.selected_store_id';

    public function selectedStoreId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if (! $user->canSwitchStoreContext() && $user->assigned_store_id) {
            return (int) $user->assigned_store_id;
        }

        $selected = $request->session()->get(self::SESSION_KEY);

        if (! $selected) {
            return null;
        }

        if (! StockLocation::query()->active()->whereKey($selected)->exists()) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return (int) $selected;
    }

    public function storesForSelector(User $user)
    {
        return StockLocation::query()
            ->active()
            ->when(! $user->canSwitchStoreContext() && $user->assigned_store_id, fn ($query) => $query->whereKey($user->assigned_store_id))
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function applyReadScope($query, Request $request, string $column = 'stock_location_id'): void
    {
        $storeId = $this->selectedStoreId($request);

        if ($storeId) {
            $query->where($column, $storeId);
        }
    }

    public function requireMutationStoreId(Request $request): int
    {
        $user = $request->user();

        if ($user?->assigned_store_id && ! $user->canSwitchStoreContext()) {
            return (int) $user->assigned_store_id;
        }

        $storeId = (int) ($request->input('stock_location_id') ?: $request->session()->get(self::SESSION_KEY));

        if ($storeId <= 0) {
            throw new \InvalidArgumentException('Select a store before saving this operation.');
        }

        return $storeId;
    }
}
