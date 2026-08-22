<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Purchase\Services\PurchaseEntryService;
use App\Domains\Store\Services\AdminStoreContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseEntryRequest;
use App\Models\PurchaseEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseEntryService $purchaseService,
        private readonly AdminStoreContextService $storeContext
    ) {}

    public function index(Request $request)
    {
        $filters = [];
        if ($storeId = $this->storeContext->selectedStoreId($request)) {
            $filters['stock_location_id'] = $storeId;
        }
        $purchases = $this->purchaseService->paginate((int) $request->input('per_page', 20), $filters);

        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $options = $this->purchaseService->options();
        $selectedStoreId = $this->storeContext->selectedStoreId(request());

        return view('admin.purchases.create', compact('options', 'selectedStoreId'));
    }

    public function store(StorePurchaseEntryRequest $request)
    {
        try {
            $data = $request->validated();
            $this->authorizeStoreWrite($request, (int) $data['stock_location_id']);
            $purchase = $this->purchaseService->create($data);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['purchase' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Purchase entry posted successfully.');
    }

    public function template(): StreamedResponse
    {
        $headers = [
            'product_name',
            'variant_name',
            'sku',
            'current_stock',
            'quantity',
            'purchase_price',
            'discount',
            'gst_rate',
            'cgst_rate',
            'sgst_rate',
            'batch_number',
            'expiry_date',
        ];

        return response()->streamDownload(function () use ($headers): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($this->purchaseService->templateRows() as $row) {
                fputcsv($output, collect($headers)->map(fn ($header) => $row[$header] ?? '')->all());
            }

            fclose($output);
        }, 'purchase-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('status', 'active')],
            'bill_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'freight_allocation' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $this->authorizeStoreWrite($request, (int) $data['stock_location_id']);

        $preview = $this->purchaseService->previewCsv($request->file('csv_file'));

        return view('admin.purchases.preview', compact('data', 'preview'));
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('status', 'active')],
            'bill_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'freight_allocation' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.cgst_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.sgst_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ]);
        $this->authorizeStoreWrite($request, (int) $data['stock_location_id']);

        $variantIds = collect($data['items'])->pluck('product_variant_id');

        if ($variantIds->count() !== $variantIds->unique()->count()) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Duplicate variants are not allowed in one purchase import.']);
        }

        try {
            $purchase = $this->purchaseService->create($data);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['purchase' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.purchases.show', $purchase)
            ->with('success', 'Purchase CSV imported successfully.');
    }

    public function show(PurchaseEntry $purchase)
    {
        $this->authorizeStoreRead(request(), $purchase);
        $purchase->load(['supplier', 'items.productVariant.product']);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function print(PurchaseEntry $purchase)
    {
        $this->authorizeStoreRead(request(), $purchase);
        $purchase->load(['supplier', 'items.productVariant.product']);

        return view('admin.purchases.print', compact('purchase'));
    }

    private function authorizeStoreWrite(Request $request, int $stockLocationId): void
    {
        $user = $request->user();

        if ($user?->assigned_store_id && ! $user->isSuperAdmin()) {
            abort_unless((int) $user->assigned_store_id === $stockLocationId, 403);
        }
    }

    private function authorizeStoreRead(Request $request, PurchaseEntry $purchase): void
    {
        $user = $request->user();

        if ($user?->assigned_store_id && ! $user->isSuperAdmin()) {
            abort_unless((int) $user->assigned_store_id === (int) $purchase->stock_location_id, 403);
        }
    }
}
