<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
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

        $recentPurchases = $supplier->purchaseEntries()
            ->withCount('items')
            ->latest('purchase_date')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.suppliers.show', compact('supplier', 'recentPurchases'));
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
