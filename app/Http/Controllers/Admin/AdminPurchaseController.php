<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Purchase\Services\PurchaseEntryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseEntryRequest;
use App\Models\PurchaseEntry;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminPurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseEntryService $purchaseService
    ) {}

    public function index(Request $request)
    {
        $purchases = $this->purchaseService->paginate((int) $request->input('per_page', 20));

        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $options = $this->purchaseService->options();

        return view('admin.purchases.create', compact('options'));
    }

    public function store(StorePurchaseEntryRequest $request)
    {
        try {
            $purchase = $this->purchaseService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['purchase' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Purchase entry posted successfully.');
    }

    public function show(PurchaseEntry $purchase)
    {
        $purchase->load(['items.productVariant.product']);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function print(PurchaseEntry $purchase)
    {
        $purchase->load(['items.productVariant.product']);

        return view('admin.purchases.print', compact('purchase'));
    }
}
