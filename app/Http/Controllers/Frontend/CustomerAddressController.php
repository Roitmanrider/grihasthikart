<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Customer\Services\CustomerAddressService;
use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Models\CustomerAddress;
use InvalidArgumentException;

class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly CustomerAddressService $addressService
    ) {}

    public function index()
    {
        $customer = $this->customer();
        $addresses = $customer->addresses()->latest()->get();

        return view('frontend.customer.addresses.index', compact('customer', 'addresses'));
    }

    public function store(StoreCustomerAddressRequest $request)
    {
        $this->addressService->create($this->customer(), $request->validated());

        return redirect()->route('customer.addresses.index')->with('success', 'Address saved successfully.');
    }

    public function edit(CustomerAddress $address)
    {
        $customer = $this->customer();
        $this->ensureOwn($customer, $address);

        return view('frontend.customer.addresses.edit', compact('customer', 'address'));
    }

    public function update(CustomerAddress $address, UpdateCustomerAddressRequest $request)
    {
        $customer = $this->customer();
        $this->ensureOwn($customer, $address);
        $this->addressService->update($address, $request->validated());

        return redirect()->route('customer.addresses.index')->with('success', 'Address updated successfully.');
    }

    public function destroy(CustomerAddress $address)
    {
        $customer = $this->customer();
        $this->ensureOwn($customer, $address);
        $this->addressService->delete($address);

        return redirect()->route('customer.addresses.index')->with('success', 'Address deleted successfully.');
    }

    public function setDefault(CustomerAddress $address)
    {
        $customer = $this->customer();
        $this->ensureOwn($customer, $address);
        $this->addressService->setDefault($address);

        return back()->with('success', 'Default address updated successfully.');
    }

    private function customer()
    {
        try {
            return $this->authService->requireCustomer(request()->session());
        } catch (InvalidArgumentException) {
            abort(redirect()->route('customer.login'));
        }
    }

    private function ensureOwn($customer, CustomerAddress $address): void
    {
        try {
            $this->addressService->ensureBelongsToCustomer($customer, $address);
        } catch (InvalidArgumentException) {
            abort(404);
        }
    }
}
