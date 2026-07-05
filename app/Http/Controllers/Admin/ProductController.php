<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\BrandService;
use App\Domains\Catalog\Services\CategoryService;
use App\Domains\Catalog\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkProductActionRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService
    ) {}

    public function index(Request $request)
    {
        $products = $this->productService->paginate(
            $request->only([
                'search',
                'brand_id',
                'category_id',
                'status',
                'is_featured',
                'is_trending',
                'is_popular',
                'is_new_arrival',
                'trashed',
                'sort',
                'direction',
            ]),
            (int) $request->input('per_page', 20)
        );

        $categories = $this->categoryService->activeCategories();
        $brands = $this->brandService->activeBrands();

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    public function create()
    {
        $categories = $this->categoryService->activeCategories();
        $brands = $this->brandService->activeBrands();

        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $this->productService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['product' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'categories']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('categories');

        $categories = $this->categoryService->activeCategories();
        $brands = $this->brandService->activeBrands();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $this->productService->update($product, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['product' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        Gate::authorize('manage-products');

        try {
            $this->productService->delete($product);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['product' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function restore(int $product)
    {
        Gate::authorize('manage-products');

        $this->productService->restore($product);

        return redirect()
            ->route('admin.products.index', ['trashed' => 'with'])
            ->with('success', 'Product restored successfully.');
    }

    public function bulkAction(BulkProductActionRequest $request)
    {
        $data = $request->validated();

        try {
            $count = match ($data['action']) {
                'delete' => $this->productService->bulkDelete($data['ids']),
                'activate' => $this->productService->bulkUpdateStatus($data['ids'], true),
                'deactivate' => $this->productService->bulkUpdateStatus($data['ids'], false),
                'restore' => $this->productService->bulkRestore($data['ids']),
            };
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['product' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.index', $request->query())
            ->with('success', $count.' products processed successfully.');
    }
}
