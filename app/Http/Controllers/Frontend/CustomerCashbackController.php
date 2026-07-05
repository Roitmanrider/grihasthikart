<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cashback\Services\CashbackRedemptionService;
use App\Domains\Cashback\Services\CashbackService;
use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequestCashbackRedemptionRequest;
use App\Models\Coupon;
use InvalidArgumentException;

class CustomerCashbackController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly CashbackService $cashbackService,
        private readonly CashbackRedemptionService $redemptionService
    ) {}

    public function index()
    {
        $customer = $this->authService->requireCustomer(request()->session());
        $rule = $this->cashbackService->defaultRule();
        $balance = $this->cashbackService->balance($customer);
        $pendingAmount = $this->cashbackService->pendingAmount($customer);
        $availableAmount = $this->cashbackService->availableBalance($customer);
        $ledgers = $customer->cashbackLedgers()->paginate(20);
        $redemptions = $customer->cashbackRedemptionRequests()->with('coupon')->take(20)->get();
        $coupons = Coupon::query()
            ->where('customer_id', $customer->id)
            ->where('source', 'cashback')
            ->latest()
            ->get();

        return view('frontend.customer.cashback.index', compact('customer', 'rule', 'balance', 'pendingAmount', 'availableAmount', 'ledgers', 'redemptions', 'coupons'));
    }

    public function redeem(RequestCashbackRedemptionRequest $request)
    {
        $customer = $this->authService->requireCustomer($request->session());

        try {
            $this->redemptionService->request(
                $customer,
                (float) $request->validated('requested_amount'),
                $request->validated('customer_note')
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cashback' => $exception->getMessage()]);
        }

        return back()->with('success', 'Cashback redemption request submitted.');
    }
}
