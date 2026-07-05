<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Customer\Contracts\CustomerRepositoryInterface;
use App\Domains\Customer\Services\CustomerAddressService;
use App\Domains\Customer\Services\CustomerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminCustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerAddressService $addressService
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->paginate(
            $request->only(['search', 'status', 'is_premium', 'cashback_enabled', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->customerService->create($request->validated());

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $customer = $this->customerRepository->findWithDetails($customer->id);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Customer $customer, UpdateCustomerRequest $request)
    {
        $this->customerService->update($customer, $request->validated());

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        Gate::authorize('manage-customers');
        $this->customerService->delete($customer);

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function restore(int $customer)
    {
        Gate::authorize('manage-customers');
        $this->customerService->restore($customer);

        return redirect()->route('admin.customers.index', ['trashed' => 'with'])->with('success', 'Customer restored successfully.');
    }

    public function status(Customer $customer)
    {
        Gate::authorize('manage-customers');
        $customer->update(['status' => ! $customer->status]);

        return back()->with('success', 'Customer status updated successfully.');
    }

    public function approveAddress(Customer $customer, CustomerAddress $address)
    {
        Gate::authorize('manage-customers');
        abort_unless($address->customer_id === $customer->id, 404);
        $this->addressService->approve($address, ! $address->is_approved);

        return back()->with('success', 'Address approval updated successfully.');
    }
}
