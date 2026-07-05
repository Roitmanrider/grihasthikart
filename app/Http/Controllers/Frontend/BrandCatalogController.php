<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\CustomerCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BrandCatalogController extends Controller
{
    public function __construct(
        private readonly CustomerCatalogService $catalogService
    ) {}

    public function index()
    {
        return view('frontend.brands.index', [
            'brands' => $this->catalogService->brandListing(),
        ]);
    }

    public function show(string $slug, Request $request)
    {
        return view('frontend.brands.show', $this->catalogService->brandDetail($slug, $request->only(['sort'])));
    }
}
