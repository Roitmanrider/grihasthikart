<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Inventory\Services\ReplenishmentService;
use App\Domains\Store\Services\AdminStoreContextService;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;

class AdminReplenishmentController extends Controller
{
    public function __construct(
        private readonly ReplenishmentService $replenishmentService,
        private readonly AdminStoreContextService $storeContext
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'stock_status',
            'supplier_id',
            'stock_location_id',
            'category_id',
            'brand_id',
            'sort',
        ]);
        if ($storeId = $this->storeContext->selectedStoreId($request)) {
            $filters['stock_location_id'] = $storeId;
        }
        $inventories = $this->replenishmentService->paginate($filters, (int) $request->input('per_page', 20));
        $summary = $this->replenishmentService->summary($filters);
        $options = $this->replenishmentService->options();

        return view('admin.inventory-replenishment.index', compact('inventories', 'summary', 'options'));
    }

    public function createPurchase(Inventory $inventory)
    {
        $user = request()->user();
        if ($user?->assigned_store_id && ! $user->isSuperAdmin()) {
            abort_unless((int) $user->assigned_store_id === (int) $inventory->stock_location_id, 403);
        }

        $prefill = $this->replenishmentService->prefillForInventory($inventory);

        return redirect()
            ->route('admin.purchases.create')
            ->withInput($prefill);
    }
}
