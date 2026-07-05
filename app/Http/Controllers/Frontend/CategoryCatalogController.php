<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\CustomerCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryCatalogController extends Controller
{
    public function __construct(
        private readonly CustomerCatalogService $catalogService
    ) {}

    public function index()
    {
        return view('frontend.categories.index', [
            'categories' => $this->catalogService->categoryListing(),
        ]);
    }

    public function show(string $slug, Request $request)
    {
        return view('frontend.categories.show', $this->catalogService->categoryDetail($slug, $request->only(['sort'])));
    }
}
