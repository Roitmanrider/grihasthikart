<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Report\Services\ReportDashboardService;
use App\Http\Controllers\Controller;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportDashboardService $reports) {}

    public function index()
    {
        return view('admin.reports.index', [
            'dashboard' => $this->reports->dashboard(),
        ]);
    }
}
