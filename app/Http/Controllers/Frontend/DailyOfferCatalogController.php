<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Catalog\Services\DailyOfferService;
use App\Http\Controllers\Controller;

class DailyOfferCatalogController extends Controller
{
    public function __construct(
        private readonly DailyOfferService $dailyOfferService
    ) {}

    public function index()
    {
        return view('frontend.daily-offers.index', [
            'dailyOffers' => $this->dailyOfferService->currentOffers(24),
        ]);
    }
}
