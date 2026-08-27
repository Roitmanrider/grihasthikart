<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLocation;
use Illuminate\Http\Request;

class AdminStoreController extends Controller
{
    public function index()
    {
        $this->authorizeStoreAdministration();

        return view('admin.stores.index', [
            'stores' => StockLocation::query()
                ->orderBy('display_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create()
    {
        $this->authorizeStoreAdministration();

        return view('admin.stores.form', [
            'store' => new StockLocation(['type' => 'store', 'status' => true, 'accepts_online_orders' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStoreAdministration();
        StockLocation::query()->create($this->validated($request));

        return redirect()->route('admin.stores.index')->with('success', 'Store created successfully.');
    }

    public function edit(StockLocation $store)
    {
        $this->authorizeStoreAdministration();

        return view('admin.stores.form', compact('store'));
    }

    public function update(Request $request, StockLocation $store)
    {
        $this->authorizeStoreAdministration();
        $store->update($this->validated($request, $store));

        return redirect()->route('admin.stores.index')->with('success', 'Store updated successfully.');
    }

    public function destroy(StockLocation $store)
    {
        $this->authorizeStoreAdministration();

        if ($store->is_default) {
            return back()->withErrors(['store' => 'Default store cannot be deactivated.']);
        }

        $store->forceFill([
            'status' => false,
            'accepts_online_orders' => false,
        ])->save();

        return redirect()->route('admin.stores.index')->with('success', 'Store deactivated successfully.');
    }

    private function authorizeStoreAdministration(): void
    {
        abort_unless(request()->user()?->canManageStores(), 403);
    }

    private function validated(Request $request, ?StockLocation $store = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:stock_locations,code,'.($store?->id ?? 'NULL').',id'],
            'type' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'accepts_online_orders' => ['nullable', 'boolean'],
        ]) + [
            'type' => 'store',
            'status' => false,
            'accepts_online_orders' => false,
            'display_order' => 0,
        ];
    }
}
