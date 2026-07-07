<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\CategoryRepositoryInterface;
use App\Models\Category;
use App\Services\MediaService;
use App\Services\SlugService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

class CategoryService
{
    protected CategoryRepositoryInterface $repository;

    public function __construct(
        CategoryRepositoryInterface $repository,
        private readonly SlugService $slugService,
        private readonly MediaService $mediaService
    ) {
        $this->repository = $repository;
    }

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function all()
    {
        return $this->repository->all();
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        $this->ensureNewCategoryActivationStateIsConsistent($data);

        return $this->persistWithUniqueSlug(function (array $preparedData) {
            return $this->repository->create($preparedData);
        }, $data);
    }

    public function update(Category $category, array $data)
    {
        $this->ensureValidParent($category, $data['parent_id'] ?? null);
        $this->ensureActivationStateIsConsistent($category, $data);

        return $this->persistWithUniqueSlug(function (array $preparedData) use ($category) {
            return $this->repository->update($category, $preparedData);
        }, $data, $category);
    }

    public function delete(Category $category)
    {
        $this->ensureCategoriesCanBeDeleted([$category->id]);

        return $this->repository->delete($category);
    }

    public function rootCategories()
    {
        return $this->repository->rootCategories();
    }

    public function activeCategories()
    {
        return $this->repository->activeCategories();
    }

    public function parentOptions(?Category $category = null)
    {
        return $this->repository->parentOptions($category?->id);
    }

    public function restore(int $id)
    {
        $category = $this->repository->findWithTrashed($id);

        if (! $category->trashed()) {
            return $category;
        }

        $category->restore();

        return $category;
    }

    public function bulkDelete(array $ids): int
    {
        $this->ensureCategoriesCanBeDeleted($ids);

        return $this->repository->bulkDelete($ids);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        if (! $status) {
            $this->ensureCategoriesCanBeDeactivated($ids);
        }

        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->repository->bulkRestore($ids);
    }

    private function persistWithUniqueSlug(callable $operation, array $data, ?Category $category = null)
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $preparedData = $this->prepareData($data, $category, $attempt);

            try {
                return $operation($preparedData);
            } catch (QueryException $exception) {
                if (! $this->isUniqueSlugViolation($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to persist category with a unique slug.');
    }

    private function prepareData(array $data, ?Category $category = null, int $attempt = 0): array
    {
        $slugSource = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['name'];

        $data['slug'] = $this->slugService->generate(
            $slugSource,
            Category::class,
            $category?->id,
            'slug',
            $attempt
        );

        $data['parent_id'] = $data['parent_id'] ?? null;
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);
        $data['show_in_menu'] = (bool) ($data['show_in_menu'] ?? false);
        $data['show_on_homepage'] = (bool) ($data['show_on_homepage'] ?? false);
        $data['status'] = (bool) ($data['status'] ?? false);

        foreach (['image', 'banner'] as $field) {
            if (($data[$field] ?? null) instanceof UploadedFile) {
                $data[$field] = $this->mediaService->replace(
                    $category?->{$field},
                    $data[$field],
                    'categories/'.$field
                );
            } elseif (array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }

            if ((bool) ($data['remove_'.$field] ?? false)) {
                $this->mediaService->delete($category?->{$field});
                $data[$field] = null;
            }

            unset($data['remove_'.$field]);
        }

        return $data;
    }

    private function ensureValidParent(Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($category->id === $parentId) {
            throw new InvalidArgumentException('A category cannot be its own parent.');
        }

        $parent = $this->repository->findWithTrashed($parentId);

        while ($parent->parent_id !== null) {
            if ($parent->parent_id === $category->id) {
                throw new InvalidArgumentException('Circular category hierarchy is not allowed.');
            }

            $parent = $this->repository->findWithTrashed($parent->parent_id);
        }
    }

    private function ensureActivationStateIsConsistent(Category $category, array $data): void
    {
        $status = (bool) ($data['status'] ?? $category->status);

        if (! $status) {
            $this->ensureCategoriesCanBeDeactivated([$category->id]);
        }

        if (($data['parent_id'] ?? null) !== null) {
            $parent = $this->repository->findWithTrashed((int) $data['parent_id']);

            if ($status && ! $parent->status) {
                throw new InvalidArgumentException('An active category cannot be assigned to an inactive parent.');
            }
        }
    }

    private function ensureNewCategoryActivationStateIsConsistent(array $data): void
    {
        $status = (bool) ($data['status'] ?? false);

        if ($status && ($data['parent_id'] ?? null) !== null) {
            $parent = $this->repository->findWithTrashed((int) $data['parent_id']);

            if (! $parent->status) {
                throw new InvalidArgumentException('An active category cannot be assigned to an inactive parent.');
            }
        }
    }

    private function ensureCategoriesCanBeDeleted(array $ids): void
    {
        if ($this->repository->idsWithChildren($ids) !== []) {
            throw new InvalidArgumentException('Parent categories with child categories cannot be deleted.');
        }
    }

    private function ensureCategoriesCanBeDeactivated(array $ids): void
    {
        if ($this->repository->idsWithActiveChildren($ids) !== []) {
            throw new InvalidArgumentException('Categories with active child categories cannot be deactivated.');
        }
    }

    private function isUniqueSlugViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'categories_slug_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: categories.slug')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
