<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\DailyOfferService;
use App\Domains\Store\Services\AdminStoreContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyOfferRequest;
use App\Http\Requests\UpdateDailyOfferRequest;
use App\Models\DailyOffer;
use App\Models\HomepageSection;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class DailyOfferController extends Controller
{
    public function __construct(
        private readonly DailyOfferService $dailyOfferService,
        private readonly AdminStoreContextService $storeContext
    ) {}

    public function index(Request $request)
    {
        $selectedStoreId = $this->storeContext->selectedStoreId($request);
        abort_if($request->user()?->isStoreManager() && $selectedStoreId === null, 403);
        $filters = $request->only(['search', 'status', 'current', 'date', 'trashed']);

        if ($selectedStoreId !== null) {
            $filters['stock_location_id'] = $selectedStoreId;
        }

        $dailyOffers = $this->dailyOfferService->paginate($filters);
        $stores = $this->storeContext->storesForSelector($request->user());
        $selectedStore = $selectedStoreId ? StockLocation::query()->find($selectedStoreId) : null;
        $canManageDailyOffers = $request->user()?->can('manage-daily-offers') ?? false;
        $sectionSettings = $this->sectionSettingsForStore($selectedStoreId);

        return view('admin.daily-offers.index', compact('dailyOffers', 'stores', 'selectedStore', 'selectedStoreId', 'canManageDailyOffers', 'sectionSettings'));
    }

    public function create(Request $request)
    {
        Gate::authorize('manage-daily-offers');
        try {
            $selectedStoreId = $this->storeContext->requireMutationStoreId($request);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('admin.daily-offers.index')->withErrors(['stock_location_id' => $exception->getMessage()]);
        }
        $variants = $this->dailyOfferService->productVariantOptions();
        $selectedStore = StockLocation::query()->findOrFail($selectedStoreId);
        $dailyOffer = new DailyOffer([
            'stock_location_id' => $selectedStoreId,
            'starts_at' => now(config('app.timezone')),
            'ends_at' => now(config('app.timezone'))->addDay(),
            'is_active' => true,
            'display_order' => 1,
            'max_quantity_per_order' => 1,
        ]);

        return view('admin.daily-offers.create', compact('variants', 'selectedStore', 'dailyOffer'));
    }

    public function store(StoreDailyOfferRequest $request)
    {
        try {
            $this->dailyOfferService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['daily_offer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.daily-offers.index')->with('success', 'Daily offer created successfully.');
    }

    public function edit(DailyOffer $dailyOffer)
    {
        Gate::authorize('manage-daily-offers');
        $this->authorizeOfferStore($dailyOffer);

        if ($dailyOffer->lifecycleState() === 'Expired') {
            return redirect()->route('admin.daily-offers.show', $dailyOffer)
                ->withErrors(['daily_offer' => 'Expired Daily Offers are read-only. Duplicate it as a new offer instead.']);
        }

        $variants = $this->dailyOfferService->productVariantOptions();
        $selectedStore = $dailyOffer->stockLocation;

        return view('admin.daily-offers.edit', compact('dailyOffer', 'variants', 'selectedStore'));
    }

    public function show(DailyOffer $dailyOffer)
    {
        $this->authorizeOfferStore($dailyOffer);
        $dailyOffer->load(['cartItems.cart', 'orderItems', 'productVariant.product', 'productVariant.inventories', 'stockLocation']);
        $canManageDailyOffers = request()->user()?->can('manage-daily-offers') ?? false;

        return view('admin.daily-offers.show', compact('dailyOffer', 'canManageDailyOffers'));
    }

    public function update(DailyOffer $dailyOffer, UpdateDailyOfferRequest $request)
    {
        $this->authorizeOfferStore($dailyOffer);

        try {
            $this->dailyOfferService->update($dailyOffer, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['daily_offer' => $exception->getMessage()]);
        }

        return redirect()->route('admin.daily-offers.index')->with('success', 'Daily offer updated successfully.');
    }

    public function destroy(DailyOffer $dailyOffer)
    {
        Gate::authorize('manage-daily-offers');
        $this->authorizeOfferStore($dailyOffer);

        if ($dailyOffer->lifecycleState() === 'Expired') {
            return back()->withErrors(['daily_offer' => 'Expired Daily Offers are read-only.']);
        }

        $this->dailyOfferService->delete($dailyOffer);

        return redirect()->route('admin.daily-offers.index')->with('success', 'Daily offer deleted successfully.');
    }

    public function restore(int $dailyOffer)
    {
        Gate::authorize('manage-daily-offers');
        $offer = DailyOffer::withTrashed()->findOrFail($dailyOffer);
        $this->authorizeOfferStore($offer);
        $this->dailyOfferService->restore($dailyOffer);

        return redirect()->route('admin.daily-offers.index', ['trashed' => 'with'])->with('success', 'Daily offer restored successfully.');
    }

    public function duplicate(DailyOffer $dailyOffer, Request $request)
    {
        Gate::authorize('manage-daily-offers');
        $this->authorizeOfferStore($dailyOffer);

        abort_unless($dailyOffer->lifecycleState() === 'Expired', 404);

        $variants = $this->dailyOfferService->productVariantOptions();
        $selectedStore = $dailyOffer->stockLocation;
        $dailyOffer = new DailyOffer([
            'stock_location_id' => $dailyOffer->stock_location_id,
            'product_variant_id' => $dailyOffer->product_variant_id,
            'title' => $dailyOffer->title,
            'offer_price' => $dailyOffer->offer_price,
            'allocated_quantity' => $dailyOffer->allocated_quantity,
            'starts_at' => now(config('app.timezone'))->addMinutes(5),
            'ends_at' => now(config('app.timezone'))->addDay(),
            'is_active' => true,
            'display_order' => 1,
            'max_quantity_per_order' => max(1, (int) $dailyOffer->max_quantity_per_order),
            'badge_text' => $dailyOffer->badge_text,
        ]);

        return view('admin.daily-offers.create', compact('variants', 'selectedStore', 'dailyOffer'));
    }

    public function updateSectionSettings(Request $request)
    {
        Gate::authorize('manage-daily-offers');
        try {
            $storeId = $this->storeContext->requireMutationStoreId($request);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('admin.daily-offers.index')->withErrors(['stock_location_id' => $exception->getMessage()]);
        }

        $data = $request->validate([
            'section_message' => ['nullable', 'string', 'max:255'],
            'auto_slide' => ['nullable', 'boolean'],
            'slide_interval' => ['required', 'integer', 'min:3', 'max:15'],
        ]);

        $section = HomepageSection::query()->firstOrNew([
            'stock_location_id' => $storeId,
            'section_key' => 'daily_offers',
        ]);

        $section->fill([
            'section_type' => 'daily_offers',
            'title' => $section->title ?: 'Daily Offers',
            'subtitle' => $data['section_message'] ?? null,
            'enabled' => true,
            'sort_order' => $section->sort_order ?: 50,
            'desktop_item_limit' => $section->desktop_item_limit ?: 8,
            'source_mode' => 'automatic',
            'view_all_enabled' => true,
            'view_all_text' => $section->view_all_text ?: 'View All',
            'configuration' => [
                'auto_slide' => $request->boolean('auto_slide'),
                'slide_interval' => (int) $data['slide_interval'],
            ],
        ])->save();

        return redirect()->route('admin.daily-offers.index')->with('success', 'Daily Offers section settings updated.');
    }

    private function authorizeOfferStore(DailyOffer $dailyOffer): void
    {
        $user = request()->user();
        $selectedStoreId = $this->storeContext->selectedStoreId(request());

        if ($user?->isStoreManager() && $selectedStoreId === null) {
            abort(403);
        }

        if ($selectedStoreId !== null) {
            abort_unless((int) $dailyOffer->stock_location_id === (int) $selectedStoreId, 403);
        }
    }

    private function sectionSettingsForStore(?int $storeId): array
    {
        $section = $storeId
            ? HomepageSection::query()->where('section_key', 'daily_offers')->where('stock_location_id', $storeId)->first()
            : null;
        $configuration = $section?->configuration ?? [];

        return [
            'section_message' => $section?->subtitle,
            'auto_slide' => (bool) ($configuration['auto_slide'] ?? true),
            'slide_interval' => (int) ($configuration['slide_interval'] ?? 5),
        ];
    }
}
