<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\CustomerCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function __construct(
        private readonly CustomerCatalogService $catalogService
    ) {}

    public function index(Request $request)
    {
        return view('frontend.products.index', [
            'products' => $this->catalogService->productListing($request->only(['search', 'category', 'brand', 'is_featured', 'is_new_arrival', 'is_popular', 'is_trending', 'sort'])),
            'categories' => $this->catalogService->activeCategories()->get(),
            'brands' => $this->catalogService->activeBrands()->get(),
        ]);
    }

    public function show(string $slug)
    {
        $product = $this->catalogService->productDetail($slug);

        return view('frontend.products.show', compact('product'));
    }
}
