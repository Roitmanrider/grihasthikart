<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\AttributeService;
use App\Domains\Catalog\Services\ProductVariantService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkProductVariantActionRequest;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantService $productVariantService,
        private readonly AttributeService $attributeService
    ) {}

    public function index(Product $product, Request $request)
    {
        $product->load(['brand', 'categories']);

        $variants = $this->productVariantService->forProduct(
            $product,
            $request->only(['search', 'status', 'is_default', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        return view('admin.product-variants.index', compact('product', 'variants'));
    }

    public function create(Product $product)
    {
        $product->load(['brand', 'categories']);
        $attributes = $this->attributeService->variantDefiningAttributes();

        return view('admin.product-variants.create', compact('product', 'attributes'));
    }

    public function store(Product $product, StoreProductVariantRequest $request)
    {
        try {
            $this->productVariantService->create($product, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['variant' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with('success', 'Product variant created successfully.');
    }

    public function show(Product $product, ProductVariant $productVariant)
    {
        $this->ensureVariantBelongsToProduct($product, $productVariant);

        $product->load(['brand', 'categories']);
        $productVariant->load(['attributeValues.attribute']);

        return view('admin.product-variants.show', compact('product', 'productVariant'));
    }

    public function edit(Product $product, ProductVariant $productVariant)
    {
        $this->ensureVariantBelongsToProduct($product, $productVariant);

        $product->load(['brand', 'categories']);
        $productVariant->load(['attributeValues.attribute']);
        $attributes = $this->attributeService->variantDefiningAttributes();

        return view('admin.product-variants.edit', compact('product', 'productVariant', 'attributes'));
    }

    public function update(Product $product, ProductVariant $productVariant, UpdateProductVariantRequest $request)
    {
        $this->ensureVariantBelongsToProduct($product, $productVariant);

        try {
            $this->productVariantService->update($productVariant, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['variant' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with('success', 'Product variant updated successfully.');
    }

    public function destroy(Product $product, ProductVariant $productVariant)
    {
        Gate::authorize('manage-product-variants');
        $this->ensureVariantBelongsToProduct($product, $productVariant);

        try {
            $this->productVariantService->delete($productVariant);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['variant' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with('success', 'Product variant deleted successfully.');
    }

    public function restore(Product $product, int $productVariant)
    {
        Gate::authorize('manage-product-variants');
        $variant = ProductVariant::withTrashed()->findOrFail($productVariant);
        $this->ensureVariantBelongsToProduct($product, $variant);

        try {
            $this->productVariantService->restore($productVariant);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['variant' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.variants.index', [$product, 'trashed' => 'with'])
            ->with('success', 'Product variant restored successfully.');
    }

    public function bulkAction(Product $product, BulkProductVariantActionRequest $request)
    {
        $data = $request->validated();

        try {
            $count = match ($data['action']) {
                'delete' => $this->productVariantService->bulkDeleteForProduct($product, $data['ids']),
                'activate' => $this->productVariantService->bulkUpdateStatusForProduct($product, $data['ids'], true),
                'deactivate' => $this->productVariantService->bulkUpdateStatusForProduct($product, $data['ids'], false),
                'restore' => $this->productVariantService->bulkRestoreForProduct($product, $data['ids']),
            };
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['variant' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.products.variants.index', array_merge([$product], $request->query()))
            ->with('success', $count.' product variants processed successfully.');
    }

    private function ensureVariantBelongsToProduct(Product $product, ProductVariant $productVariant): void
    {
        abort_unless($productVariant->product_id === $product->id, 404);
    }
}
