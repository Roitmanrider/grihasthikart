@extends('layouts.admin')

@section('title', 'Customer Details')

@section('admin-content')
<div class="d-flex justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $customer->name }}</h1>
        <div class="text-muted">{{ $customer->mobile }}</div>
    </div>
    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-success">Edit</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Profile</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Email</dt>
                    <dd class="col-7">{{ $customer->email ?: 'None' }}</dd>
                    <dt class="col-5">Status</dt>
                    <dd class="col-7">{{ $customer->status ? 'Active' : 'Inactive' }}</dd>
                    <dt class="col-5">Premium</dt>
                    <dd class="col-7">{{ $customer->is_premium ? 'Yes' : 'No' }}</dd>
                    <dt class="col-5">Delivery Rules</dt>
                    <dd class="col-7">{{ $customer->custom_delivery_rules_enabled ? 'Custom' : 'Inherited' }}</dd>
                    <dt class="col-5">Last Login</dt>
                    <dd class="col-7">{{ $customer->last_login_at?->format('d M Y, h:i A') ?: 'Never' }}</dd>
                </dl>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Delivery Rules</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Customer Tier</dt>
                    <dd class="col-7">{{ str($deliveryRule['customer_tier'])->headline() }}</dd>
                    <dt class="col-5">Custom Delivery Rules</dt>
                    <dd class="col-7">{{ $customer->custom_delivery_rules_enabled ? 'ON' : 'OFF' }}</dd>
                    <dt class="col-5">Minimum Order</dt>
                    <dd class="col-7">{{ $customer->minimum_order_amount_override === null ? 'Inherit '.$deliveryRule['sources']['minimum_order_amount'] : 'Rs. '.number_format((float) $customer->minimum_order_amount_override, 2) }}</dd>
                    <dt class="col-5">Delivery Charge</dt>
                    <dd class="col-7">{{ $customer->delivery_charge_override === null ? 'Inherit '.$deliveryRule['sources']['delivery_charge'] : 'Rs. '.number_format((float) $customer->delivery_charge_override, 2) }}</dd>
                    <dt class="col-5">Free Delivery Threshold</dt>
                    <dd class="col-7">{{ $customer->free_delivery_threshold_override === null ? 'Inherit '.$deliveryRule['sources']['free_delivery_threshold'] : 'Rs. '.number_format((float) $customer->free_delivery_threshold_override, 2) }}</dd>
                    <dt class="col-5">Effective Minimum Order</dt>
                    <dd class="col-7">Rs. {{ number_format((float) $deliveryRule['minimum_order_amount'], 2) }}</dd>
                    <dt class="col-5">Effective Delivery Charge</dt>
                    <dd class="col-7">{{ (float) $deliveryRule['delivery_charge_configured'] > 0 ? 'Rs. '.number_format((float) $deliveryRule['delivery_charge_configured'], 2) : 'Free' }}</dd>
                    <dt class="col-5">Effective Free Threshold</dt>
                    <dd class="col-7">{{ $deliveryRule['free_delivery_threshold'] === null ? 'None' : 'Rs. '.number_format((float) $deliveryRule['free_delivery_threshold'], 2) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Addresses</div>
            <div class="card-body">
                @forelse ($customer->addresses as $address)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <div class="fw-semibold">{{ $address->label ?: 'Address' }}</div>

                            @if ($address->is_default)
                                <span class="badge text-bg-success rounded-pill px-3 py-2">Default</span>
                            @endif

                            <span class="badge {{ $address->is_approved ? 'text-bg-primary' : 'text-bg-warning' }} rounded-pill px-3 py-2">
                                {{ $address->is_approved ? 'Approved' : 'Pending approval' }}
                            </span>

                            @if (! $address->status)
                                <span class="badge text-bg-secondary rounded-pill px-3 py-2">Inactive</span>
                            @endif
                        </div>

                        <div class="small">
                            <div><span class="text-muted">Recipient:</span> {{ $address->recipient_name ?: $customer->name }}</div>
                            <div><span class="text-muted">Phone:</span> {{ $address->mobile ?: $customer->mobile }}</div>
                            <div>{{ $address->address_line1 }}</div>

                            @if ($address->address_line2)
                                <div>{{ $address->address_line2 }}</div>
                            @endif

                            @if ($address->landmark)
                                <div><span class="text-muted">Landmark:</span> {{ $address->landmark }}</div>
                            @endif

                            <div>{{ $address->city }}, {{ $address->state }} - {{ $address->pincode }}</div>
                        </div>

                        <form method="POST" action="{{ route('admin.customers.addresses.approve', [$customer, $address]) }}" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-success">{{ $address->is_approved ? 'Unapprove' : 'Approve' }}</button>
                        </form>
                    </div>
                @empty
                    <div class="text-muted">No addresses.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold">Orders</div>
    <div class="card-body">
        @forelse ($customer->orders as $order)
            <div>
                <a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                - Rs. {{ number_format((float) $order->grand_total, 2) }}
            </div>
        @empty
            <div class="text-muted">No orders.</div>
        @endforelse
    </div>
</div>
@endsection
