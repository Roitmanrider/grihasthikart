<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Models\Order;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService
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
            ->with('items')
            ->firstOrFail();

        return view('frontend.customer.orders.show', compact('customer', 'order'));
    }

    private function requireCustomer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (\InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }
}
