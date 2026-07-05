<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\ProductImageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductImageService $productImageService
    ) {}

    public function store(Product $product, StoreProductImageRequest $request)
    {
        try {
            $this->productImageService->create($product, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['image' => $exception->getMessage()]);
        }

        return back()->with('success', 'Product images uploaded successfully.');
    }

    public function storeVariant(Product $product, ProductVariant $productVariant, StoreProductImageRequest $request)
    {
        try {
            $this->productImageService->create($product, $request->validated(), $productVariant);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['image' => $exception->getMessage()]);
        }

        return back()->with('success', 'Variant images uploaded successfully.');
    }

    public function edit(Product $product, ProductImage $productImage)
    {
        $this->authorizeProductImage($product, $productImage);

        return view('admin.product-images.edit', compact('product', 'productImage'));
    }

    public function update(Product $product, ProductImage $productImage, UpdateProductImageRequest $request)
    {
        $this->authorizeProductImage($product, $productImage);
        $this->productImageService->update($productImage, $request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Product image updated successfully.');
    }

    public function setPrimary(Product $product, ProductImage $productImage)
    {
        Gate::authorize('manage-product-images');
        $this->authorizeProductImage($product, $productImage);
        $this->productImageService->setPrimary($productImage);

        return back()->with('success', 'Primary image updated successfully.');
    }

    public function destroy(Product $product, ProductImage $productImage)
    {
        Gate::authorize('manage-product-images');
        $this->authorizeProductImage($product, $productImage);

        try {
            $this->productImageService->delete($productImage);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['image' => $exception->getMessage()]);
        }

        return back()->with('success', 'Product image deleted successfully.');
    }

    public function restore(Product $product, int $productImage)
    {
        Gate::authorize('manage-product-images');
        $image = ProductImage::withTrashed()->findOrFail($productImage);
        $this->authorizeProductImage($product, $image);
        $this->productImageService->restore($productImage);

        return back()->with('success', 'Product image restored successfully.');
    }

    public function editVariant(Product $product, ProductVariant $productVariant, ProductImage $productImage)
    {
        $this->authorizeVariantImage($product, $productVariant, $productImage);

        return view('admin.product-images.edit', compact('product', 'productVariant', 'productImage'));
    }

    public function updateVariant(Product $product, ProductVariant $productVariant, ProductImage $productImage, UpdateProductImageRequest $request)
    {
        $this->authorizeVariantImage($product, $productVariant, $productImage);
        $this->productImageService->update($productImage, $request->validated());

        return redirect()
            ->route('admin.products.variants.edit', [$product, $productVariant])
            ->with('success', 'Variant image updated successfully.');
    }

    public function setVariantPrimary(Product $product, ProductVariant $productVariant, ProductImage $productImage)
    {
        Gate::authorize('manage-product-images');
        $this->authorizeVariantImage($product, $productVariant, $productImage);
        $this->productImageService->setPrimary($productImage);

        return back()->with('success', 'Primary variant image updated successfully.');
    }

    public function destroyVariant(Product $product, ProductVariant $productVariant, ProductImage $productImage)
    {
        Gate::authorize('manage-product-images');
        $this->authorizeVariantImage($product, $productVariant, $productImage);

        try {
            $this->productImageService->delete($productImage);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['image' => $exception->getMessage()]);
        }

        return back()->with('success', 'Variant image deleted successfully.');
    }

    public function restoreVariant(Product $product, ProductVariant $productVariant, int $productImage)
    {
        Gate::authorize('manage-product-images');
        $image = ProductImage::withTrashed()->findOrFail($productImage);
        $this->authorizeVariantImage($product, $productVariant, $image);
        $this->productImageService->restore($productImage);

        return back()->with('success', 'Variant image restored successfully.');
    }

    private function authorizeProductImage(Product $product, ProductImage $productImage): void
    {
        $this->productImageService->ensureImageBelongsToProduct($product, $productImage);

        if ($productImage->product_variant_id !== null) {
            abort(404);
        }
    }

    private function authorizeVariantImage(Product $product, ProductVariant $productVariant, ProductImage $productImage): void
    {
        abort_unless($productVariant->product_id === $product->id, 404);
        $this->productImageService->ensureImageBelongsToProduct($product, $productImage);
        $this->productImageService->ensureImageBelongsToVariant($productVariant, $productImage);
    }
}
