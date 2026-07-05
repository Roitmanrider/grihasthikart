<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\BrandRepositoryInterface;
use App\Models\Brand;
use App\Services\MediaService;
use App\Services\SlugService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BrandService
{
    public function __construct(
        private readonly BrandRepositoryInterface $repository,
        private readonly SlugService $slugService,
        private readonly MediaService $mediaService
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function activeBrands()
    {
        return $this->repository->activeBrands();
    }

    public function featuredBrands()
    {
        return $this->repository->featuredBrands();
    }

    public function create(array $data)
    {
        [$brandData, $mediaFiles] = $this->extractMediaFiles($data);

        return $this->persistWithUniqueSlug(function (array $preparedData) use ($mediaFiles) {
            return $this->persistWithMedia(
                fn () => $this->repository->create($preparedData),
                $mediaFiles
            );
        }, $brandData);
    }

    public function update(Brand $brand, array $data)
    {
        [$brandData, $mediaFiles] = $this->extractMediaFiles($data);

        return $this->persistWithUniqueSlug(function (array $preparedData) use ($brand, $mediaFiles) {
            return $this->persistWithMedia(
                fn () => $this->repository->update($brand, $preparedData),
                $mediaFiles,
                $brand
            );
        }, $brandData, $brand);
    }

    public function delete(Brand $brand)
    {
        return $this->repository->delete($brand);
    }

    public function restore(int $id)
    {
        $brand = $this->repository->findWithTrashed($id);

        if (! $brand->trashed()) {
            return $brand;
        }

        $brand->restore();

        return $brand;
    }

    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDelete($ids);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->repository->bulkRestore($ids);
    }

    private function persistWithUniqueSlug(callable $operation, array $data, ?Brand $brand = null)
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $preparedData = $this->prepareData($data, $brand, $attempt);

            try {
                return $operation($preparedData);
            } catch (QueryException $exception) {
                if (! $this->isUniqueSlugViolation($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to persist brand with a unique slug.');
    }

    private function prepareData(array $data, ?Brand $brand = null, int $attempt = 0): array
    {
        $slugSource = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['name'];

        $data['slug'] = $this->slugService->generate(
            $slugSource,
            Brand::class,
            $brand?->id,
            'slug',
            $attempt
        );

        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['status'] = (bool) ($data['status'] ?? false);

        return $data;
    }

    private function persistWithMedia(callable $operation, array $mediaFiles, ?Brand $existingBrand = null): Brand
    {
        $storedPaths = [];
        $oldPaths = [];

        try {
            $brand = DB::transaction(function () use ($operation, $mediaFiles, $existingBrand, &$storedPaths, &$oldPaths) {
                /** @var Brand $brand */
                $brand = $operation();
                $mediaData = [];

                foreach ($mediaFiles as $field => $file) {
                    $oldPaths[] = $existingBrand?->{$field};
                    $storedPaths[$field] = $this->mediaService->store($file, 'brands/'.$field);
                    $mediaData[$field] = $storedPaths[$field];
                }

                if ($mediaData !== []) {
                    $brand = $this->repository->update($brand, $mediaData);
                }

                return $brand;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);

            throw $exception;
        }

        $this->deleteStoredPaths($oldPaths);

        return $brand;
    }

    private function extractMediaFiles(array $data): array
    {
        $mediaFiles = [];

        foreach (['logo', 'banner'] as $field) {
            if (($data[$field] ?? null) instanceof UploadedFile) {
                $mediaFiles[$field] = $data[$field];
            }

            unset($data[$field]);
        }

        return [$data, $mediaFiles];
    }

    private function deleteStoredPaths(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            $this->mediaService->delete($path);
        }
    }

    private function isUniqueSlugViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'brands_slug_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: brands.slug')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
