<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Order\Services\OrderService;
use App\Domains\Order\Services\OrderStatusService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelCustomerOrderRequest;
use App\Models\Order;
use InvalidArgumentException;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly OrderStatusService $orderStatusService,
        private readonly OrderService $orderService,
        private readonly BusinessSettingService $settingService
    ) {}

    public function dashboard()
    {
        $customer = $this->requireCustomer();
        $customer->loadCount('addresses');
        $orders = $customer->orders()->latest('placed_at')->take(5)->get();

        return view('frontend.customer.dashboard', compact('customer', 'orders'));
    }

    public function orders()
    {
        $customer = $this->requireCustomer();
        $orders = $customer->orders()->latest('placed_at')->paginate(10);

        return view('frontend.customer.orders.index', compact('customer', 'orders'));
    }

    public function orderShow(string $orderNumber)
    {
        $customer = $this->requireCustomer();
        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'statusHistories'])
            ->firstOrFail();
        $statusTimeline = $this->orderStatusService->timelineFor($order);
        $canCancel = $this->orderStatusService->canCustomerCancel($order);
        $customerInvoiceEnabled = $this->settingService->customerInvoiceEnabled();

        return view('frontend.customer.orders.show', compact('customer', 'order', 'statusTimeline', 'canCancel', 'customerInvoiceEnabled'));
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
