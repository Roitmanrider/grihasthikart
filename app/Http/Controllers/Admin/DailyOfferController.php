<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\DailyOfferService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyOfferRequest;
use App\Http\Requests\UpdateDailyOfferRequest;
use App\Models\DailyOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class DailyOfferController extends Controller
{
    public function __construct(
        private readonly DailyOfferService $dailyOfferService
    ) {}

    public function index(Request $request)
    {
        $dailyOffers = $this->dailyOfferService->paginate($request->only(['search', 'status', 'current', 'date', 'trashed']));

        return view('admin.daily-offers.index', compact('dailyOffers'));
    }

    public function create()
    {
        $variants = $this->dailyOfferService->productVariantOptions();

        return view('admin.daily-offers.create', compact('variants'));
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
        $variants = $this->dailyOfferService->productVariantOptions();

        return view('admin.daily-offers.edit', compact('dailyOffer', 'variants'));
    }

    public function show(DailyOffer $dailyOffer)
    {
        $dailyOffer->load(['productVariant.product', 'productVariant.inventories']);

        return view('admin.daily-offers.show', compact('dailyOffer'));
    }

    public function update(DailyOffer $dailyOffer, UpdateDailyOfferRequest $request)
    {
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
        $this->dailyOfferService->delete($dailyOffer);

        return redirect()->route('admin.daily-offers.index')->with('success', 'Daily offer deleted successfully.');
    }

    public function restore(int $dailyOffer)
    {
        Gate::authorize('manage-daily-offers');
        $this->dailyOfferService->restore($dailyOffer);

        return redirect()->route('admin.daily-offers.index', ['trashed' => 'with'])->with('success', 'Daily offer restored successfully.');
    }
}
