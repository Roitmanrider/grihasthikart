<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Inventory\Services\StockAdjustmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockVerificationRequest;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminStockVerificationController extends Controller
{
    public function __construct(
        private readonly StockAdjustmentService $stockAdjustmentService
    ) {}

    public function index(Request $request)
    {
        $stockAdjustments = $this->stockAdjustmentService->paginateVerifications((int) $request->input('per_page', 20));

        return view('admin.stock-verifications.index', compact('stockAdjustments'));
    }

    public function create()
    {
        $options = $this->stockAdjustmentService->options();

        return view('admin.stock-verifications.create', compact('options'));
    }

    public function store(StoreStockVerificationRequest $request)
    {
        try {
            $stockAdjustment = $this->stockAdjustmentService->createVerification($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['stock_verification' => $exception->getMessage()]);
        }

        $message = (float) $stockAdjustment->quantity > 0
            ? 'Stock verification recorded and stock adjusted.'
            : 'Stock verification recorded. System stock matched physical count.';

        return redirect()
            ->route('admin.stock-verifications.index')
            ->with('success', $message);
    }
}
