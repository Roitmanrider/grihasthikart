<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Models\CustomerCartRiskMonthly;
use App\Models\PendingOrder;
use Illuminate\Http\Request;

class AdminPendingOrderController extends Controller
{
    public function index(Request $request)
    {
        $employeeFollowupEnabled = app(BusinessSettingService::class)
            ->get('checkout.cart_employee_followup_enabled', true);
        $query = PendingOrder::query()
            ->with(['customer', 'convertedOrder', 'cart.items.productVariant'])
            ->orderBy('expires_at');

        $status = (string) $request->string('status', PendingOrder::STATUS_ACTIVE);

        if ($status !== '') {
            $query->where('status', $status);

            if ($status === PendingOrder::STATUS_ACTIVE) {
                $query->where('expires_at', '>', now());
            }
        }

        if ($request->string('filter')->toString() === 'whatsapp_due') {
            $query->whereNull('whatsapp_reminder_attempted_at')
                ->whereNotNull('whatsapp_reminder_due_at')
                ->where('whatsapp_reminder_due_at', '<=', now());
        }

        if ($request->string('filter')->toString() === 'call_followup') {
            $employeeFollowupEnabled
                ? $query->whereNotNull('follow_up_updated_at')->where('follow_up_status', 'NOT_CONTACTED')
                : $query->whereRaw('1 = 0');
        }

        if ($request->string('filter')->toString() === 'scarce_stock') {
            $query->where('scarce_stock_hold', true);
        }

        if ($request->string('filter')->toString() === 'watch') {
            $query->where('risk_level', 'WATCH');
        }

        if ($request->string('filter')->toString() === 'high_risk') {
            $query->where('risk_level', 'HIGH_RISK');
        }

        if ($request->filled('from')) {
            $query->whereDate('started_at', '>=', (string) $request->date('from')?->toDateString());
        }

        if ($request->filled('to')) {
            $query->whereDate('started_at', '<=', (string) $request->date('to')?->toDateString());
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($query) use ($search) {
                $query->where('reference', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('mobile', 'like', '%'.$search.'%');
                    });
            });
        }

        $pendingOrders = $query->paginate(20)->withQueryString();

        return view('admin.pending-orders.index', compact('pendingOrders', 'employeeFollowupEnabled'));
    }

    public function show(PendingOrder $pendingOrder)
    {
        $pendingOrder->load(['customer', 'convertedOrder', 'cart.items.productVariant.inventories', 'items.productVariant']);
        $riskHistory = CustomerCartRiskMonthly::query()
            ->where('customer_id', $pendingOrder->customer_id)
            ->latest('period_month')
            ->limit(6)
            ->get();

        return view('admin.pending-orders.show', compact('pendingOrder', 'riskHistory'));
    }
}
