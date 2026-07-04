<?php

namespace App\Domains\Catalog\Services;

use App\Models\Category;
use App\Domains\Catalog\Repositories\CategoryRepositoryInterface;

class CategoryService
{
    protected CategoryRepositoryInterface $repository;

    public function __construct(CategoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function paginate($perPage = 20)
    {
        return $this->repository->paginate($perPage);
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
        return $this->repository->create($data);
    }

    public function update(Category $category, array $data)
    {
        return $this->repository->update($category, $data);
    }

    public function delete(Category $category)
    {
        return $this->repository->delete($category);
    }

    public function rootCategories()
    {
        return Category::root()
            ->orderBy('sort_order')
            ->get();
    }
}
