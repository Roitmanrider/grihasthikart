<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cashback\Services\CashbackService;
use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Customer\Services\CustomerCreditService;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Services\OrderStatusService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Staff\Services\DeliveryOtpAccessService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelCustomerOrderRequest;
use App\Models\Coupon;
use App\Models\Order;
use InvalidArgumentException;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly OrderStatusService $orderStatusService,
        private readonly OrderService $orderService,
        private readonly BusinessSettingService $settingService,
        private readonly CustomerCreditService $customerCreditService,
        private readonly CashbackService $cashbackService,
        private readonly DeliveryOtpAccessService $deliveryOtpAccessService
    ) {}

    public function dashboard()
    {
        $customer = $this->requireCustomer();
        $customer->loadCount([
            'addresses',
            'approvedAddresses',
            'notifications as unread_notifications_count' => fn ($query) => $query->unread(),
        ]);
        $orders = $customer->orders()->latest('placed_at')->take(5)->get();
        $creditBalance = $this->customerCreditService->balance($customer);
        $creditTransactions = $this->customerCreditService->recent($customer, 5);
        $cashbackAvailable = $this->cashbackService->availableBalance($customer);
        $availableCouponsCount = Coupon::query()
            ->where('status', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(function ($query) use ($customer) {
                $query->where('audience', Coupon::AUDIENCE_PUBLIC)
                    ->orWhere(function ($query) use ($customer) {
                        $query->where('audience', Coupon::AUDIENCE_CUSTOMER_SPECIFIC)
                            ->where(function ($query) use ($customer) {
                                $query->where('customer_id', $customer->id)
                                    ->orWhereHas('assignedCustomers', fn ($assigned) => $assigned->where('customers.id', $customer->id));
                            });
                    });
            })
            ->count();

        return view('frontend.customer.dashboard', compact('customer', 'orders', 'creditBalance', 'creditTransactions', 'availableCouponsCount', 'cashbackAvailable'));
    }

    public function orders()
    {
        $customer = $this->requireCustomer();
        $orders = $customer->orders()
            ->with('returnRequests')
            ->latest('placed_at')
            ->paginate(10);

        return view('frontend.customer.orders.index', compact('customer', 'orders'));
    }

    public function credit()
    {
        $customer = $this->requireCustomer();
        $creditBalance = $this->customerCreditService->balance($customer);
        $creditTransactions = $customer->creditTransactions()
            ->with(['order', 'returnRequest'])
            ->paginate(15);

        return view('frontend.customer.credit.index', compact('customer', 'creditBalance', 'creditTransactions'));
    }

    public function orderShow(string $orderNumber)
    {
        $customer = $this->requireCustomer();
        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'statusHistories', 'returnRequests'])
            ->firstOrFail();
        $statusTimeline = $this->orderStatusService->timelineFor($order);
        $canCancel = $this->orderStatusService->canCustomerCancel($order);
        $customerInvoiceEnabled = $this->settingService->customerInvoiceEnabled();
        $deliveryOtpCode = $this->deliveryOtpAccessService->activeCodeForCustomerOrder($customer, $order);

        return view('frontend.customer.orders.show', compact('customer', 'order', 'statusTimeline', 'canCancel', 'customerInvoiceEnabled', 'deliveryOtpCode'));
    }

    public function cancelOrder(string $orderNumber, CancelCustomerOrderRequest $request)
    {
        $customer = $this->requireCustomer();
        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        try {
            $this->orderService->cancelByCustomer($order, $request->validated('reason'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        return redirect()
            ->route('customer.orders.show', $order->order_number)
            ->with('success', 'Order cancelled successfully.');
    }

    private function requireCustomer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }
}
