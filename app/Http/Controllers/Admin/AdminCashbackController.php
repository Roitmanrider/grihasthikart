<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Cashback\Services\CashbackCalculationService;
use App\Domains\Cashback\Services\CashbackRedemptionService;
use App\Domains\Cashback\Services\CashbackService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveCashbackRedemptionRequest;
use App\Http\Requests\ProcessCashbackRequest;
use App\Http\Requests\RejectCashbackRedemptionRequest;
use App\Http\Requests\StoreCashbackRuleRequest;
use App\Http\Requests\UpdateCashbackRuleRequest;
use App\Models\CashbackRedemptionRequest;
use App\Models\CashbackRule;
use App\Models\Customer;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminCashbackController extends Controller
{
    public function __construct(
        private readonly CashbackService $cashbackService,
        private readonly CashbackCalculationService $calculationService,
        private readonly CashbackRedemptionService $redemptionService
    ) {}

    public function index()
    {
        $stats = $this->cashbackService->dashboardStats();

        return view('admin.cashback.index', compact('stats'));
    }

    public function rules()
    {
        $rules = CashbackRule::query()->latest()->paginate(20);

        return view('admin.cashback.rules.index', compact('rules'));
    }

    public function createRule()
    {
        return view('admin.cashback.rules.create', ['rule' => new CashbackRule]);
    }

    public function storeRule(StoreCashbackRuleRequest $request)
    {
        $this->cashbackService->createRule($request->validated());

        return redirect()->route('admin.cashback.rules.index')->with('success', 'Cashback rule created successfully.');
    }

    public function editRule(CashbackRule $rule)
    {
        return view('admin.cashback.rules.edit', compact('rule'));
    }

    public function updateRule(UpdateCashbackRuleRequest $request, CashbackRule $rule)
    {
        $this->cashbackService->updateRule($rule, $request->validated());

        return redirect()->route('admin.cashback.rules.index')->with('success', 'Cashback rule updated successfully.');
    }

    public function customer(Customer $customer)
    {
        $balance = $this->cashbackService->balance($customer);
        $ledgers = $customer->cashbackLedgers()->paginate(20);
        $summaries = $customer->cashbackMonthlySummaries()->take(12)->get();
        $redemptions = $customer->cashbackRedemptionRequests()->with('coupon')->take(20)->get();

        return view('admin.cashback.customer', compact('customer', 'balance', 'ledgers', 'summaries', 'redemptions'));
    }

    public function process(ProcessCashbackRequest $request)
    {
        $data = $request->validated();
        $count = 0;

        if ($data['customer_id'] ?? null) {
            $count = $this->calculationService->processEligibleCashbackForMonth(
                Customer::query()->findOrFail($data['customer_id']),
                (int) $data['year'],
                (int) $data['month']
            );
        } else {
            Customer::query()->where('cashback_enabled', true)->get()->each(function (Customer $customer) use (&$count, $data) {
                $count += $this->calculationService->processEligibleCashbackForMonth($customer, (int) $data['year'], (int) $data['month']);
            });
        }

        return back()->with('success', $count.' cashback ledger entries processed.');
    }

    public function redemptions(Request $request)
    {
        $redemptions = CashbackRedemptionRequest::query()
            ->with('customer', 'coupon')
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('admin.cashback.redemptions.index', compact('redemptions'));
    }

    public function redemptionShow(CashbackRedemptionRequest $redemption)
    {
        $redemption->load('customer', 'coupon');

        return view('admin.cashback.redemptions.show', compact('redemption'));
    }

    public function approve(CashbackRedemptionRequest $redemption, ApproveCashbackRedemptionRequest $request)
    {
        try {
            $this->redemptionService->approve($redemption, (float) $request->validated('approved_amount'), $request->validated('admin_note'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cashback' => $exception->getMessage()]);
        }

        return back()->with('success', 'Redemption request approved.');
    }

    public function reject(CashbackRedemptionRequest $redemption, RejectCashbackRedemptionRequest $request)
    {
        $this->redemptionService->reject($redemption, $request->validated('admin_note'));

        return back()->with('success', 'Redemption request rejected.');
    }

    public function generateCoupon(CashbackRedemptionRequest $redemption)
    {
        try {
            $this->redemptionService->generateCoupon($redemption);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cashback' => $exception->getMessage()]);
        }

        return back()->with('success', 'Cashback coupon generated successfully.');
    }
}
