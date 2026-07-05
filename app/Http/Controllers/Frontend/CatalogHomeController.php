<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\CustomerCatalogService;
use App\Http\Controllers\Controller;

class CatalogHomeController extends Controller
{
    public function __construct(
        private readonly CustomerCatalogService $catalogService
    ) {}

    public function __invoke()
    {
        return view('frontend.home.index', $this->catalogService->homepageData());
    }
}
