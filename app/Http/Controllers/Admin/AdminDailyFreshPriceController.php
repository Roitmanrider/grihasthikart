<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Store\Services\StoreVariantPriceService;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StoreVariantPrice;
use Illuminate\Http\Request;

class AdminDailyFreshPriceController extends Controller
{
    public function __construct(private readonly StoreVariantPriceService $priceService) {}

    public function index(Request $request)
    {
        $assignedStoreId = $this->assignedStoreId($request);
        $store = $assignedStoreId
            ? StockLocation::query()->find($assignedStoreId)
            : StockLocation::query()->find($request->integer('stock_location_id'))
            ?? StockLocation::query()->where('is_default', true)->first()
            ?? StockLocation::query()->orderBy('id')->first();

        return view('admin.daily-fresh-prices.index', [
            'stores' => StockLocation::query()
                ->active()
                ->when($assignedStoreId, fn ($query) => $query->whereKey($assignedStoreId))
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
            'store' => $store,
            'prices' => StoreVariantPrice::query()
                ->with(['productVariant.product', 'changedBy'])
                ->when($store, fn ($query) => $query->where('stock_location_id', $store->id))
                ->orderByDesc('updated_at')
                ->paginate(25)
                ->withQueryString(),
            'variants' => ProductVariant::query()
                ->with('product')
                ->active()
                ->whereHas('product.categories', fn ($query) => $query->where('rapid_price_update_enabled', true))
                ->orderBy('sku')
                ->limit(200)
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stock_location_id' => ['required', 'exists:stock_locations,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['nullable', 'boolean'],
            'change_reason' => ['nullable', 'string', 'max:255'],
        ]);
        $this->authorizeStoreAccess($request, (int) $data['stock_location_id']);

        $this->priceService->updatePrice(
            StockLocation::query()->findOrFail($data['stock_location_id']),
            ProductVariant::query()->findOrFail($data['product_variant_id']),
            $data,
            $request->user()
        );

        return redirect()
            ->route('admin.daily-fresh-prices.index', ['stock_location_id' => $data['stock_location_id']])
            ->with('success', 'Store price updated successfully.');
    }

    private function assignedStoreId(Request $request): ?int
    {
        $user = $request->user();

        return $user?->assigned_store_id && ! $user->isSuperAdmin() ? (int) $user->assigned_store_id : null;
    }

    private function authorizeStoreAccess(Request $request, int $stockLocationId): void
    {
        $assignedStoreId = $this->assignedStoreId($request);

        if ($assignedStoreId) {
            abort_unless($assignedStoreId === $stockLocationId, 403);
        }
    }
}
