<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\CustomerCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CatalogAutocompleteController extends Controller
{
    public function __construct(
        private readonly CustomerCatalogService $catalogService
    ) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return response()->json([
            'data' => $this->catalogService->autocomplete((string) ($data['q'] ?? ''), 10),
        ]);
    }
}
