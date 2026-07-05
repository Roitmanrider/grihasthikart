<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Report\Services\TaxReportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaxReportFilterRequest;
use App\Models\Order;

class AdminTaxReportController extends Controller
{
    public function __construct(
        private readonly TaxReportService $taxReportService
    ) {}

    public function gstSummary(TaxReportFilterRequest $request)
    {
        $filters = $this->taxReportService->filters($request->validated());
        $summary = $this->taxReportService->gstSummary($filters);

        return view('admin.reports.gst-summary', compact('summary', 'filters'));
    }

    public function gstByRate(TaxReportFilterRequest $request)
    {
        $filters = $this->taxReportService->filters($request->validated());
        $rows = $this->taxReportService->gstByRate($filters);

        return view('admin.reports.gst-by-rate', compact('rows', 'filters'));
    }

    public function gstMonthly(TaxReportFilterRequest $request)
    {
        $filters = $this->taxReportService->filters($request->validated());
        $rows = $this->taxReportService->monthly($filters);

        return view('admin.reports.gst-monthly', compact('rows', 'filters'));
    }

    public function orderTax(Order $order)
    {
        $detail = $this->taxReportService->orderTaxDetail($order);

        return view('admin.orders.tax', $detail);
    }
}
