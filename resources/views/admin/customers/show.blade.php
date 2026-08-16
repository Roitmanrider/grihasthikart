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
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Customer Credit</div>
            <div class="card-body">
                <div class="h4 mb-3">Rs. {{ number_format((float) $creditBalance, 2) }}</div>
                @forelse ($creditTransactions as $transaction)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold">{{ str($transaction->type)->headline() }}</div>
                                <div class="small text-muted">{{ $transaction->description ?: $transaction->source }}</div>
                                @if ($transaction->order)
                                    <a class="small" href="{{ route('admin.orders.show', $transaction->order) }}">{{ $transaction->order->order_number }}</a>
                                @endif
                                @if ($transaction->returnRequest)
                                    <a class="small" href="{{ route('admin.returns.show', $transaction->returnRequest) }}">{{ $transaction->returnRequest->return_number }}</a>
                                @endif
                            </div>
                            <div class="text-end">
                                <strong>Rs. {{ number_format((float) $transaction->amount, 2) }}</strong>
                                <div class="small text-muted">Balance: Rs. {{ number_format((float) $transaction->balance_after, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">No Customer Credit transactions yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center gap-2">
                <span>Addresses</span>
                <span class="badge text-bg-light border">{{ $customer->addresses->count() }}</span>
            </div>
            <div class="card-body">
                <details class="border rounded p-3 mb-4">
                    <summary class="fw-semibold">Add address for customer</summary>
                    <form method="POST" action="{{ route('admin.customers.addresses.store', $customer) }}" class="row g-3 mt-1">
                        @csrf
                        @include('admin.customers.address-form', ['address' => null, 'customer' => $customer])
                        <div class="col-12">
                            <button class="btn btn-success btn-sm">Save Approved Address</button>
                        </div>
                    </form>
                </details>

                @forelse ($customer->addresses as $address)
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <div class="fw-semibold">{{ $address->label ?: 'Address' }}</div>

                            @if ($address->is_default)
                                <span class="badge text-bg-success rounded-pill px-3 py-2">Default</span>
                            @endif

                            <span class="badge {{ $address->is_approved ? 'text-bg-primary' : (($address->approval_status ?? 'PENDING') === 'REJECTED' ? 'text-bg-danger' : 'text-bg-warning') }} rounded-pill px-3 py-2">
                                {{ $address->is_approved ? 'Approved' : str(strtolower($address->approval_status ?? 'PENDING'))->headline() }}
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
                            @if (($address->approval_status ?? null) === 'REJECTED' && $address->rejection_reason)
                                <div class="text-danger mt-1"><span class="text-muted">Reason:</span> {{ $address->rejection_reason }}</div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.customers.addresses.approve', [$customer, $address]) }}" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-outline-success" name="decision" value="approve">{{ $address->is_approved ? 'Unapprove' : 'Approve' }}</button>
                                @unless ($address->is_approved)
                                    <input name="rejection_reason" class="form-control form-control-sm" style="max-width: 280px;" placeholder="Rejection reason">
                                    <button class="btn btn-sm btn-outline-danger" name="decision" value="reject">Reject</button>
                                @endunless
                            </div>
                        </form>

                        @if ($address->is_approved && $address->status && ! $address->is_default)
                            <form method="POST" action="{{ route('admin.customers.addresses.default', [$customer, $address]) }}" class="mt-2">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-primary">Set as Default</button>
                            </form>
                        @endif

                        <details class="mt-3">
                            <summary class="small fw-semibold text-success">Edit address</summary>
                            <form method="POST" action="{{ route('admin.customers.addresses.update', [$customer, $address]) }}" class="row g-3 mt-1">
                                @csrf
                                @method('PATCH')
                                @include('admin.customers.address-form', ['address' => $address, 'customer' => $customer])
                                <div class="col-12">
                                    <button class="btn btn-sm btn-success">Update Address</button>
                                </div>
                            </form>
                        </details>
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
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date/Time</th>
                        <th>Merchandise Amount</th>
                        <th>Delivery Charge</th>
                        <th>Customer Credit</th>
                        <th>Final Amount</th>
                        <th>Fulfillment</th>
                        <th>Return Status</th>
                        <th>Delivered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customer->orders as $order)
                        @php($latestReturn = $order->returnRequests->sortByDesc('created_at')->first())
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->placed_at?->format('d M Y, h:i A') ?: '-' }}</td>
                            <td>Rs. {{ number_format((float) $order->subtotal, 2) }}</td>
                            <td>{{ (float) $order->delivery_charge > 0 ? 'Rs. '.number_format((float) $order->delivery_charge, 2) : 'Free' }}</td>
                            <td>{{ (float) $order->customer_credit_used > 0 ? '- Rs. '.number_format((float) $order->customer_credit_used, 2) : '-' }}</td>
                            <td>Rs. {{ number_format((float) $order->grand_total, 2) }}</td>
                            <td><span class="badge text-bg-light">{{ str($order->order_status)->headline() }}</span></td>
                            <td>{{ $latestReturn ? str($latestReturn->status)->headline() : '-' }}</td>
                            <td>{{ $order->delivered_at?->format('d M Y, h:i A') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted">No orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
