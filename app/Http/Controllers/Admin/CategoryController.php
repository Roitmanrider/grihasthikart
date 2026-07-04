<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkCategoryActionRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

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

    public function index(Request $request)
    {
        $categories = $this->categoryService->paginate(
            $request->only(['search', 'status', 'is_featured', 'parent_id', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        $parents = $this->categoryService->parentOptions();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $parents = $this->categoryService->parentOptions();

        return view('admin.categories.create', compact('parents'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(StoreCategoryRequest $request)
    {
        try {
            $this->categoryService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['parent_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
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
        $parents = $this->categoryService->parentOptions($category);

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $this->categoryService->update(
                $category,
                $request->validated()
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['parent_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(Category $category)
    {
        Gate::authorize('manage-categories');

        try {
            $this->categoryService->delete($category);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['category' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    public function restore(int $category)
    {
        Gate::authorize('manage-categories');

        $this->categoryService->restore($category);

        return redirect()
            ->route('admin.categories.index', ['trashed' => 'with'])
            ->with('success', 'Category restored successfully.');
    }

    public function bulkAction(BulkCategoryActionRequest $request)
    {
        $data = $request->validated();

        try {
            $count = match ($data['action']) {
                'delete' => $this->categoryService->bulkDelete($data['ids']),
                'activate' => $this->categoryService->bulkUpdateStatus($data['ids'], true),
                'deactivate' => $this->categoryService->bulkUpdateStatus($data['ids'], false),
                'restore' => $this->categoryService->bulkRestore($data['ids']),
            };
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['category' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.categories.index', $request->query())
            ->with('success', $count.' categories processed successfully.');
    }
}
