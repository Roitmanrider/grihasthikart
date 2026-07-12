<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Inventory\Services\StockAdjustmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminStockAdjustmentController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentService $stockAdjustmentService
    ) {}

    public function index(Request $request)
    {
        $stockAdjustments = $this->stockAdjustmentService->paginate((int) $request->input('per_page', 20));

        return view('admin.stock-adjustments.index', compact('stockAdjustments'));
    }

    public function create()
    {
        $options = $this->stockAdjustmentService->options();

        return view('admin.stock-adjustments.create', compact('options'));
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        try {
            $stockAdjustment = $this->stockAdjustmentService->createAdjustment($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['stock_adjustment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.stock-adjustments.show', $stockAdjustment)
            ->with('success', 'Stock adjustment recorded successfully.');
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['productVariant.product', 'inventory.stockLocation', 'creator', 'movements']);

        return view('admin.stock-adjustments.show', compact('stockAdjustment'));
    }
}
