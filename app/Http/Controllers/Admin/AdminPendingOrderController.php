<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Models\CustomerCartRiskMonthly;
use App\Models\PendingOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPendingOrderController extends Controller
{
    public function index(Request $request)
    {
        $employeeFollowupEnabled = app(BusinessSettingService::class)
            ->get('checkout.cart_employee_followup_enabled', true);
        $query = PendingOrder::query()
            ->with(['customer', 'assignedAdmin', 'convertedOrder', 'cart.items.productVariant']);

        $status = (string) $request->string('status', PendingOrder::STATUS_ACTIVE);
        $filters = collect((array) $request->input('filters', []))
            ->merge($request->filled('filter') ? [(string) $request->string('filter')] : [])
            ->filter()
            ->unique()
            ->values();

        if ($filters->contains('unassigned') && ($filters->contains('assigned_me') || $request->filled('assigned_admin_user_id'))) {
            return back()->withErrors(['filters' => 'Choose either Unassigned or an assigned employee filter, not both.']);
        }

        if ($status !== '') {
            $query->where('status', $status);

            if ($status === PendingOrder::STATUS_ACTIVE) {
                $query->where('expires_at', '>', now());
            }
        }

        if ($filters->contains('call_followup')) {
            $employeeFollowupEnabled
                ? $this->applyFollowUpEligibility($query)
                : $query->whereRaw('1 = 0');
        }

        $this->applyFilters($query, $filters, $request);

        if ($request->filled('assigned_admin_user_id')) {
            $query->where('assigned_admin_user_id', (int) $request->integer('assigned_admin_user_id'));
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

        $this->applySort($query, (string) $request->string('sort', 'most_recently_active'));

        $followUpBase = PendingOrder::query()
            ->where('status', PendingOrder::STATUS_ACTIVE)
            ->where('expires_at', '>', now());

        if ($employeeFollowupEnabled) {
            $this->applyFollowUpEligibility($followUpBase);
        } else {
            $followUpBase->whereRaw('1 = 0');
        }

        $quickCounts = $this->quickCounts($followUpBase);
        $assignableAdmins = User::query()
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        $pendingOrders = $query->paginate(20)->withQueryString();

        return view('admin.pending-orders.index', compact('pendingOrders', 'employeeFollowupEnabled', 'filters', 'quickCounts', 'assignableAdmins'));
    }

    public function show(PendingOrder $pendingOrder)
    {
        $pendingOrder->load(['customer', 'assignedAdmin', 'convertedOrder', 'cart.items.productVariant.inventories', 'items.productVariant']);
        $riskHistory = CustomerCartRiskMonthly::query()
            ->where('customer_id', $pendingOrder->customer_id)
            ->latest('period_month')
            ->limit(6)
            ->get();

        return view('admin.pending-orders.show', compact('pendingOrder', 'riskHistory'));
    }

    public function updateFollowUp(PendingOrder $pendingOrder, Request $request)
    {
        $data = $request->validate([
            'follow_up_status' => ['required', Rule::in(['NOT_CONTACTED', 'CALLED', 'WILL_ORDER', 'NO_ANSWER', 'NOT_INTERESTED', 'WATCH_CUSTOMER'])],
        ]);

        $pendingOrder->update([
            'follow_up_status' => $data['follow_up_status'],
            'follow_up_updated_at' => now(),
        ]);

        return back()->with('success', 'Follow-up status updated.');
    }

    public function assign(PendingOrder $pendingOrder, Request $request)
    {
        $data = $request->validate([
            'assigned_admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $pendingOrder->update([
            'assigned_admin_user_id' => $data['assigned_admin_user_id'] ?? auth()->id(),
            'follow_up_updated_at' => now(),
        ]);

        return back()->with('success', 'Cart follow-up assignment updated.');
    }

    public function unassign(PendingOrder $pendingOrder)
    {
        $pendingOrder->update([
            'assigned_admin_user_id' => null,
            'follow_up_updated_at' => now(),
        ]);

        return back()->with('success', 'Cart follow-up assignment cleared.');
    }

    private function applyFollowUpEligibility($query): void
    {
        $query->whereNotNull('follow_up_eligible_at')
            ->where('expires_at', '>', now())
            ->where('status', PendingOrder::STATUS_ACTIVE);
    }

    private function applyFilters($query, $filters, Request $request): void
    {
        $highValueMinimum = max(0, (float) $request->input('high_value_min', 1000));
        $expiringSoonCutoff = now()->addMinutes(max(1, min(
            (int) app(BusinessSettingService::class)->get('checkout.cart_hold_minutes', 60),
            (int) app(BusinessSettingService::class)->get('checkout.cart_reminder_minutes', 30),
        )));

        foreach ($filters as $filter) {
            match ($filter) {
                'expiring_soon' => $query->where('expires_at', '<=', $expiringSoonCutoff),
                'scarce_stock' => $query->where('scarce_stock_hold', true),
                'high_risk' => $query->where('risk_level', 'HIGH_RISK'),
                'watch' => $query->where('risk_level', 'WATCH'),
                'high_cart_value' => $query->where('cart_value_snapshot', '>=', $highValueMinimum),
                'oldest_waiting' => $query->whereNotNull('follow_up_eligible_at'),
                'premium' => $query->whereHas('customer', fn ($query) => $query->where('is_premium', true)),
                'whatsapp_due' => $query->whereNull('whatsapp_reminder_attempted_at')->whereNotNull('whatsapp_reminder_due_at')->where('whatsapp_reminder_due_at', '<=', now()),
                'whatsapp_sent' => $query->where('whatsapp_reminder_status', 'SENT'),
                'whatsapp_failed' => $query->where('whatsapp_reminder_status', 'FAILED'),
                'not_contacted' => $query->where('follow_up_status', 'NOT_CONTACTED'),
                'called' => $query->where('follow_up_status', 'CALLED'),
                'no_answer' => $query->where('follow_up_status', 'NO_ANSWER'),
                'will_order' => $query->where('follow_up_status', 'WILL_ORDER'),
                'not_interested' => $query->where('follow_up_status', 'NOT_INTERESTED'),
                'daily_offer' => $query->whereHas('cart.items', fn ($query) => $query->where('sale_type', 'daily_offer')->whereNotNull('daily_offer_id')),
                'unassigned' => $query->whereNull('assigned_admin_user_id'),
                'assigned_me' => $query->where('assigned_admin_user_id', auth()->id()),
                default => null,
            };
        }
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'highest_cart_value' => $query->orderByDesc('cart_value_snapshot'),
            'oldest_waiting' => $query->orderBy('follow_up_eligible_at')->orderBy('expires_at'),
            'highest_risk' => $query->orderByRaw("case risk_level when 'HIGH_RISK' then 3 when 'WATCH' then 2 else 1 end desc")->orderBy('expires_at'),
            'premium_first' => $query->orderByRaw('exists (select 1 from customers where customers.id = pending_orders.customer_id and customers.is_premium = 1) desc')->orderBy('expires_at'),
            'most_scarce_stock' => $query->orderByDesc('scarce_stock_hold')->orderBy('expires_at'),
            'most_recently_active' => $query->orderByDesc('last_activity_at'),
            default => $query->orderBy('expires_at'),
        };
    }

    private function quickCounts($baseQuery): array
    {
        $expiringSoonCutoff = now()->addMinutes(max(1, min(
            (int) app(BusinessSettingService::class)->get('checkout.cart_hold_minutes', 60),
            (int) app(BusinessSettingService::class)->get('checkout.cart_reminder_minutes', 30),
        )));

        return [
            'need_follow_up' => (clone $baseQuery)->count(),
            'expiring_soon' => (clone $baseQuery)->where('expires_at', '<=', $expiringSoonCutoff)->count(),
            'scarce_stock' => (clone $baseQuery)->where('scarce_stock_hold', true)->count(),
            'high_risk' => (clone $baseQuery)->where('risk_level', 'HIGH_RISK')->count(),
            'premium' => (clone $baseQuery)->whereHas('customer', fn ($query) => $query->where('is_premium', true))->count(),
            'whatsapp_failed' => (clone $baseQuery)->where('whatsapp_reminder_status', 'FAILED')->count(),
            'unassigned' => (clone $baseQuery)->whereNull('assigned_admin_user_id')->count(),
        ];
    }
}
