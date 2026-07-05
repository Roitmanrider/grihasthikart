<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Inventory\Contracts\InventoryRepositoryInterface;
use App\Domains\Inventory\Services\InventoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Requests\BulkInventoryActionRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function index(Request $request)
    {
        $inventories = $this->inventoryService->paginate(
            $request->only(['search', 'product_variant_id', 'stock_location_id', 'status', 'low_stock', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );
        $options = $this->inventoryService->options();

        return view('admin.inventories.index', compact('inventories', 'options'));
    }

    public function create()
    {
        $options = $this->inventoryService->options();

        return view('admin.inventories.create', compact('options'));
    }

    public function store(StoreInventoryRequest $request)
    {
        $data = $request->validated();

        try {
            $this->inventoryService->createInventory(
                ProductVariant::findOrFail($data['product_variant_id']),
                StockLocation::findOrFail($data['stock_location_id']),
                $data
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['inventory' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.inventories.index')
            ->with('success', 'Inventory record created successfully.');
    }

    public function show(Inventory $inventory)
    {
        $inventory = $this->inventoryRepository->findWithRelations($inventory->id);
        $movements = $this->inventoryRepository->movementHistory($inventory);

        return view('admin.inventories.show', compact('inventory', 'movements'));
    }

    public function edit(Inventory $inventory)
    {
        $inventory->load(['productVariant.product', 'stockLocation']);

        return view('admin.inventories.edit', compact('inventory'));
    }

    public function update(Inventory $inventory, UpdateInventoryRequest $request)
    {
        $this->inventoryService->update($inventory, $request->validated());

        return redirect()
            ->route('admin.inventories.show', $inventory)
            ->with('success', 'Inventory record updated successfully.');
    }

    public function adjust(Inventory $inventory)
    {
        $inventory->load(['productVariant.product', 'stockLocation']);

        return view('admin.inventories.adjust', compact('inventory'));
    }

    public function storeAdjustment(Inventory $inventory, AdjustInventoryRequest $request)
    {
        $data = $request->validated();

        try {
            $this->inventoryService->adjustStock(
                $inventory,
                $data['movement_type'],
                (float) $data['quantity'],
                $data['note'] ?? null
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['inventory' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.inventories.show', $inventory)
            ->with('success', 'Inventory adjusted successfully.');
    }

    public function destroy(Inventory $inventory)
    {
        Gate::authorize('manage-inventory');
        $this->inventoryService->delete($inventory);

        return redirect()
            ->route('admin.inventories.index')
            ->with('success', 'Inventory record deleted successfully.');
    }

    public function restore(int $inventory)
    {
        Gate::authorize('manage-inventory');
        $this->inventoryService->restore($inventory);

        return redirect()
            ->route('admin.inventories.index', ['trashed' => 'with'])
            ->with('success', 'Inventory record restored successfully.');
    }

    public function bulkAction(BulkInventoryActionRequest $request)
    {
        $data = $request->validated();

        $count = match ($data['action']) {
            'activate' => $this->inventoryService->bulkUpdateStatus($data['ids'], true),
            'deactivate' => $this->inventoryService->bulkUpdateStatus($data['ids'], false),
            'delete' => $this->inventoryService->bulkDelete($data['ids']),
            'restore' => $this->inventoryService->bulkRestore($data['ids']),
        };

        return redirect()
            ->route('admin.inventories.index', $request->query())
            ->with('success', $count.' inventory records processed successfully.');
    }
}
