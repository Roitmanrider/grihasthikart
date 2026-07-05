@extends('layouts.frontend')

@section('title', 'Checkout - GrihasthiKart')
@section('description', 'Place your GrihasthiKart order.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Checkout</h1>
                    <p class="text-muted mb-0">Choose a delivery slot and payment option.</p>
                </div>
                <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary">Back to Cart</a>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-semibold">Delivery Details</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('checkout.place') }}" class="row g-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="customer_mobile" value="{{ old('customer_mobile', $customer?->mobile) }}" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" value="{{ old('customer_email', $customer?->email) }}" class="form-control">
                                </div>
                                @if ($approvedAddresses->isNotEmpty())
                                    <div class="col-12">
                                        <div class="alert alert-light border">
                                            <div class="fw-semibold mb-2">Saved approved addresses</div>
                                            @foreach ($approvedAddresses as $address)
                                                <div class="small text-muted">
                                                    {{ $address->label ?: 'Address' }}: {{ $address->address_line1 }}, {{ $address->city }} - {{ $address->pincode }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label class="form-label">Address Line 1</label>
                                    <input type="text" name="delivery_address_line1" value="{{ old('delivery_address_line1') }}" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address Line 2</label>
                                    <input type="text" name="delivery_address_line2" value="{{ old('delivery_address_line2') }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" name="delivery_city" value="{{ old('delivery_city') }}" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" name="delivery_state" value="{{ old('delivery_state') }}" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="delivery_pincode" value="{{ old('delivery_pincode') }}" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Landmark</label>
                                    <input type="text" name="delivery_landmark" value="{{ old('delivery_landmark') }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Date</label>
                                    <input type="date" name="delivery_date" value="{{ old('delivery_date') }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Slot</label>
                                    <select name="delivery_slot" class="form-select">
                                        <option value="">No preference</option>
                                        @foreach ($deliverySlots as $slot)
                                            <option value="{{ $slot->label }}" @selected(old('delivery_slot') === $slot->label)>{{ $slot->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Order Notes</label>
                                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Payment Method</label>
                                    <div class="row g-2">
                                        @foreach ($enabledPaymentMethods as $method)
                                            <div class="col-md-4">
                                                <label class="border rounded p-3 d-block h-100">
                                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="{{ $method }}" @checked(old('payment_method', $enabledPaymentMethods[0] ?? 'cod') === $method) required>
                                                    <span class="fw-semibold">
                                                        @if ($method === 'cod')
                                                            Cash on Delivery
                                                        @elseif ($method === 'qr')
                                                            {{ $paymentSettings['qr_label'] ?? 'Pay by QR' }}
                                                        @else
                                                            Online Payment
                                                        @endif
                                                    </span>
                                                    <span class="small text-muted d-block mt-1">
                                                        @if ($method === 'cod')
                                                            Pay when your order arrives.
                                                        @elseif ($method === 'qr')
                                                            Place order and upload payment proof.
                                                        @else
                                                            Razorpay-ready payment flow.
                                                        @endif
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if (empty($enabledPaymentMethods))
                                        <div class="alert alert-warning mt-2 mb-0">No payment method is currently available.</div>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-success btn-lg" type="submit" @disabled(empty($enabledPaymentMethods))>Place Order</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-semibold">Order Summary</div>
                        <div class="card-body">
                            @foreach ($cart->items as $item)
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <div>
                                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                        <div class="small text-muted">{{ $item->variant_name_snapshot }} x {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</div>
                                    </div>
                                    <div>Rs. {{ number_format($item->line_total, 2) }}</div>
                                </div>
                            @endforeach

                            <div class="d-flex justify-content-between mt-3">
                                <span>Subtotal</span>
                                <strong>Rs. {{ number_format($subtotal, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Delivery Charge</span>
                                <strong>Rs. {{ number_format($checkoutSettings['delivery_charge'], 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-success">
                                <span>Savings</span>
                                <strong>Rs. {{ number_format($savings, 2) }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between h5">
                                <span>Grand Total</span>
                                <strong>Rs. {{ number_format($subtotal + $checkoutSettings['delivery_charge'], 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
