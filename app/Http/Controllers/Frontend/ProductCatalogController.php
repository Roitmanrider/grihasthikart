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
        $filters = $request->only([
            'q',
            'search',
            'category',
            'brand',
            'brands',
            'min_price',
            'max_price',
            'weight',
            'weights',
            'discount_min',
            'is_featured',
            'is_new_arrival',
            'is_popular',
            'is_trending',
            'sort',
        ]);
        $meta = $this->catalogService->listingMeta($filters);

        return view('frontend.products.index', [
            'products' => $this->catalogService->productListing($filters),
            'categories' => $this->catalogService->activeCategories()->get(),
            'brands' => $this->catalogService->activeBrands()->get(),
            'filters' => $meta['filters'],
            'filterOptions' => $meta['filterOptions'],
            'categorySuggestions' => $meta['categorySuggestions'],
        ]);
    }

    public function show(string $slug)
    {
        $product = $this->catalogService->productDetail($slug);

        return view('frontend.products.show', compact('product'));
    }
}
