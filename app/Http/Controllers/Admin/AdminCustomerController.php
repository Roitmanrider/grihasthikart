<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Customer\Contracts\CustomerRepositoryInterface;
use App\Domains\Customer\Services\CustomerAddressService;
use App\Domains\Customer\Services\CustomerCreditService;
use App\Domains\Customer\Services\CustomerService;
use App\Domains\Customer\Services\CustomerSessionService;
use App\Domains\Delivery\Services\DeliveryChargeService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminCustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerAddressService $addressService,
        private readonly CustomerSessionService $customerSessionService,
        private readonly DeliveryChargeService $deliveryChargeService,
        private readonly CustomerCreditService $customerCreditService
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerService->paginate(
            $request->only(['search', 'status', 'is_premium', 'cashback_enabled', 'pending_addresses', 'trashed', 'sort', 'direction']),
            (int) $request->input('per_page', 20)
        );

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        $deliveryRule = $this->deliveryChargeService->resolve();
        $stores = $this->storesForForm();

        return view('admin.customers.create', compact('deliveryRule', 'stores'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->customerService->create($request->validated());

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        $customer = $this->customerRepository->findWithDetails($customer->id);
        $deliveryRule = $this->deliveryChargeService->resolve($customer);
        $creditBalance = $this->customerCreditService->balance($customer);
        $creditTransactions = $this->customerCreditService->recent($customer, 10);

        return view('admin.customers.show', compact('customer', 'deliveryRule', 'creditBalance', 'creditTransactions'));
    }

    public function edit(Customer $customer)
    {
        $deliveryRule = $this->deliveryChargeService->resolve($customer);
        $stores = $this->storesForForm();

        return view('admin.customers.edit', compact('customer', 'deliveryRule', 'stores'));
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
        $newStatus = ! $customer->status;
        $customer->update(['status' => $newStatus]);

        if (! $newStatus) {
            $this->customerSessionService->revokeAllForCustomer($customer);
        }

        return back()->with('success', 'Customer status updated successfully.');
    }

    public function approveAddress(Customer $customer, CustomerAddress $address)
    {
        Gate::authorize('manage-customers');
        abort_unless($address->customer_id === $customer->id, 404);
        $reason = trim((string) request('rejection_reason', '')) ?: null;
        $approved = request()->input('decision') === 'reject' ? false : ! $address->is_approved;
        $this->addressService->approve($address, $approved, $approved ? null : $reason);

        return back()->with('success', 'Address approval updated successfully.');
    }

    public function storeAddress(Customer $customer, Request $request)
    {
        Gate::authorize('manage-customers');
        $data = $this->validateAddress($request);
        $data['is_approved'] = $request->boolean('is_approved', true);
        $data['status'] = $request->boolean('status', true);

        $this->addressService->createByAdmin($customer, $data);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer address created successfully.');
    }

    public function updateAddress(Customer $customer, CustomerAddress $address, Request $request)
    {
        Gate::authorize('manage-customers');
        abort_unless($address->customer_id === $customer->id, 404);
        $data = $this->validateAddress($request);
        $data['is_approved'] = $request->boolean('is_approved', $address->is_approved);
        $data['status'] = $request->boolean('status', $address->status);

        $this->addressService->updateByAdmin($address, $data);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer address updated successfully.');
    }

    public function setDefaultAddress(Customer $customer, CustomerAddress $address)
    {
        Gate::authorize('manage-customers');
        abort_unless($address->customer_id === $customer->id, 404);
        $this->addressService->setDefault($address);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Default address updated successfully.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'min:10', 'max:15'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:10'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:25', 'max:5000'],
            'is_default' => ['nullable', 'boolean'],
            'is_approved' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function storesForForm()
    {
        return StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get();
    }
}
