<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\BrandService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkBrandActionRequest;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brandService) {}

    public function index(Request $request)
    {
        $brands = $this->brandService->paginate(
            $request->only(['search', 'status', 'is_featured', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        return view('admin.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request)
    {
        $this->brandService->create($request->validated());

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function show(Brand $brand)
    {
        return view('admin.brands.show', compact('brand'));
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $this->brandService->update($brand, $request->validated());

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        Gate::authorize('manage-brands');

        $this->brandService->delete($brand);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully.');
    }

    public function restore(int $brand)
    {
        Gate::authorize('manage-brands');

        $this->brandService->restore($brand);

        return redirect()
            ->route('admin.brands.index', ['trashed' => 'with'])
            ->with('success', 'Brand restored successfully.');
    }

    public function bulkAction(BulkBrandActionRequest $request)
    {
        $data = $request->validated();

        $count = match ($data['action']) {
            'delete' => $this->brandService->bulkDelete($data['ids']),
            'activate' => $this->brandService->bulkUpdateStatus($data['ids'], true),
            'deactivate' => $this->brandService->bulkUpdateStatus($data['ids'], false),
            'restore' => $this->brandService->bulkRestore($data['ids']),
        };

        return redirect()
            ->route('admin.brands.index', $request->query())
            ->with('success', $count.' brands processed successfully.');
    }
}
