<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Report\Services\ReportDashboardService;
use App\Domains\Store\Services\AdminStoreContextService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly ReportDashboardService $reports,
        private readonly AdminStoreContextService $storeContext
    ) {}

    public function index(Request $request)
    {
        return view('admin.reports.index', [
            'dashboard' => $this->reports->dashboard($this->storeContext->selectedStoreId($request)),
        ]);
    }
}
