<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\DailyOfferService;
use App\Domains\Store\Services\StoreContextService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DailyOfferCatalogController extends Controller
{
    public function __construct(
        private readonly DailyOfferService $dailyOfferService,
        private readonly StoreContextService $storeContextService
    ) {}

    public function index(Request $request)
    {
        $store = $this->storeContextService->resolveFromSession($request->session());

        return view('frontend.daily-offers.index', [
            'dailyOffers' => $this->dailyOfferService->currentOffers(24, $store?->id),
        ]);
    }
}
