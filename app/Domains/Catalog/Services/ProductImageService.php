<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\ProductImageRepositoryInterface;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class ProductImageService
{
    public function __construct(
        private readonly ProductImageRepositoryInterface $repository,
        private readonly MediaService $mediaService
    ) {}

    public function productImages(Product $product, array $filters = [])
    {
        return $this->repository->forProduct($product->id, $filters);
    }

    public function variantImages(ProductVariant $variant, array $filters = [])
    {
        return $this->repository->forVariant($variant->id, $filters);
    }

    public function create(Product $product, array $data, ?ProductVariant $variant = null)
    {
        $this->ensureVariantBelongsToProduct($product, $variant);

        $files = $data['images'] ?? [];
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($product, $variant, $data, $files, &$storedPaths) {
                $created = collect();

                foreach ($files as $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $storedPaths[] = $this->mediaService->store($file, $this->directoryFor($product, $variant));

                    $image = $this->repository->create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'path' => end($storedPaths),
                        'alt_text' => $data['alt_text'] ?? null,
                        'title' => $data['title'] ?? null,
                        'display_order' => $data['display_order'] ?? 0,
                        'is_primary' => false,
                        'status' => (bool) ($data['status'] ?? true),
                    ]);

                    $created->push($image);
                }

                if ($created->isNotEmpty() && (bool) ($data['is_primary'] ?? false)) {
                    $this->setPrimary($created->first());
                }

                return $created;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);

            throw $exception;
        }
    }

    public function update(ProductImage $image, array $data): ProductImage
    {
        return DB::transaction(function () use ($image, $data) {
            $image = $this->repository->update($image, [
                'alt_text' => $data['alt_text'] ?? null,
                'title' => $data['title'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'status' => (bool) ($data['status'] ?? true),
                'is_primary' => (bool) ($data['is_primary'] ?? false),
            ]);

            if ($image->is_primary) {
                $this->setPrimary($image);
            }

            return $image;
        });
    }

    public function setPrimary(ProductImage $image): ProductImage
    {
        return DB::transaction(function () use ($image) {
            if ($image->product_variant_id !== null) {
                $this->repository->clearPrimaryForVariant($image->product_variant_id, $image->id);
            } else {
                $this->repository->clearPrimaryForProduct($image->product_id, $image->id);
            }

            return $this->repository->update($image, ['is_primary' => true]);
        });
    }

    public function delete(ProductImage $image)
    {
        if ($image->is_primary) {
            throw new InvalidArgumentException('Primary images cannot be deleted until another image is made primary.');
        }

        return $this->repository->delete($image);
    }

    public function restore(int $id): ProductImage
    {
        $image = $this->repository->findWithTrashed($id);

        if (! $image->trashed()) {
            return $image;
        }

        $this->repository->restore($image);

        return $image;
    }

    public function ensureImageBelongsToProduct(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw new InvalidArgumentException('This image does not belong to the selected product.');
        }
    }

    public function ensureImageBelongsToVariant(ProductVariant $variant, ProductImage $image): void
    {
        if ($image->product_variant_id !== $variant->id) {
            throw new InvalidArgumentException('This image does not belong to the selected variant.');
        }
    }

    private function ensureVariantBelongsToProduct(Product $product, ?ProductVariant $variant): void
    {
        if ($variant !== null && $variant->product_id !== $product->id) {
            throw new InvalidArgumentException('The selected variant does not belong to this product.');
        }
    }

    private function directoryFor(Product $product, ?ProductVariant $variant): string
    {
        if ($variant !== null) {
            return 'products/'.$product->id.'/variants/'.$variant->id;
        }

        return 'products/'.$product->id;
    }

    private function deleteStoredPaths(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            $this->mediaService->delete($path);
        }
    }
}
