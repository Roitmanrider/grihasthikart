<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Domains\Catalog\Services\CategoryService;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

use App\Models\Category;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /*
    |--------------------------------------------------------------------------
    | List Categories
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $categories = $this->categoryService->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $parents = $this->categoryService->rootCategories();

        return view('admin.categories.create', compact('parents'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService->create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('success','Category created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Category $category)
    {
        $parents = $this->categoryService->rootCategories();

        return view('admin.categories.edit', compact('category','parents'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->categoryService->update(
            $category,
            $request->validated()
        );

        return redirect()
            ->route('categories.index')
            ->with('success','Category updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(Category $category)
    {
        $this->categoryService->delete($category);

        return redirect()
            ->route('categories.index')
            ->with('success','Category deleted successfully.');
    }
}
