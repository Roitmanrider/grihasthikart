<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerProfileRequest;
use InvalidArgumentException;

class CustomerProfileController extends Controller
{
    public function __construct(private readonly CustomerAuthService $authService) {}

    public function edit()
    {
        $customer = $this->customer();

        return view('frontend.customer.profile', compact('customer'));
    }

    public function update(UpdateCustomerProfileRequest $request)
    {
        $customer = $this->customer();
        $customer->update($request->validated());

        return redirect()->route('customer.profile.edit')->with('success', 'Profile updated successfully.');
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
