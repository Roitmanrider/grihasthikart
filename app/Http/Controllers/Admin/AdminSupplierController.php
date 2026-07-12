<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AdminSupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->withCount('purchaseEntries')
            ->search($request->input('search'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        Supplier::query()->create($this->payload($request->validated()));

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->loadCount('purchaseEntries');

        $filters = request()->only(['year', 'date_from', 'date_to']);
        $purchaseQuery = $supplier->purchaseEntries()
            ->withCount('items')
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('purchase_date', $year))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('purchase_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('purchase_date', '<=', $date));

        $summary = [
            'purchase_count' => (clone $purchaseQuery)->count(),
            'purchase_total' => round((float) (clone $purchaseQuery)->sum('grand_total'), 2),
            'freight_total' => round((float) (clone $purchaseQuery)->sum('freight_allocation'), 2),
            'discount_total' => round((float) (clone $purchaseQuery)->sum('discount_total'), 2),
            'gst_total' => round((float) (clone $purchaseQuery)->sum('gst_total'), 2),
            'cgst_total' => round((float) (clone $purchaseQuery)->sum('cgst_total'), 2),
            'sgst_total' => round((float) (clone $purchaseQuery)->sum('sgst_total'), 2),
        ];

        $years = $supplier->purchaseEntries()
            ->orderByDesc('purchase_date')
            ->pluck('purchase_date')
            ->map(fn ($date) => $date ? Carbon::parse($date)->format('Y') : null)
            ->filter()
            ->unique()
            ->values();

        $recentPurchases = $purchaseQuery
            ->latest('purchase_date')
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.suppliers.show', compact('supplier', 'recentPurchases', 'filters', 'summary', 'years'));
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($this->payload($request->validated()));

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        Gate::authorize('manage-inventory');

        if ($supplier->purchaseEntries()->exists()) {
            return redirect()
                ->route('admin.suppliers.index')
                ->withErrors(['supplier' => 'This supplier has purchase entries and cannot be deleted. Mark it inactive instead.']);
        }

        $supplier->delete();

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    private function payload(array $data): array
    {
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        return $data;
    }
}
