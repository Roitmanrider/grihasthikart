<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

class MediaResolver
{
    public function __construct(
        private readonly MediaService $mediaService
    ) {}

    public function url(?string $path): ?string
    {
        return $this->mediaService->url($path);
    }

    public function categoryImage(?Category $category): ?string
    {
        while ($category) {
            if ($category->image) {
                return $category->image;
            }

            $category = $category->parent;
        }

        return null;
    }

    public function productImage(?Product $product, ?ProductVariant $variant = null): ?string
    {
        if ($variant?->primaryImage?->path) {
            return $variant->primaryImage->path;
        }

        if ($product?->primaryImage?->path) {
            return $product->primaryImage->path;
        }

        if ($product?->categories) {
            $category = $product->categories
                ->sortByDesc(fn (Category $category) => (bool) $category->pivot?->is_primary)
                ->first();

            return $this->categoryImage($category);
        }

        return null;
    }

    public function productImageUrl(?Product $product, ?ProductVariant $variant = null): ?string
    {
        return $this->url($this->productImage($product, $variant));
    }
}
