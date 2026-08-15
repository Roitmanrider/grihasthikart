<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use InvalidArgumentException;

class CustomerCouponController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function index()
    {
        $customer = $this->customer();
        $coupons = Coupon::query()
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
            ->latest()
            ->paginate(12);

        return view('frontend.customer.coupons', compact('customer', 'coupons'));
    }

    private function customer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }
}
