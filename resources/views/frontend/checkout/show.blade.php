@extends('layouts.frontend')

@section('title', 'Checkout - GrihasthiKart')
@section('description', 'Place your GrihasthiKart order.')

@section('content')
    <section class="py-5 js-cart-sync" data-cart-revision="{{ $cart->revision }}" data-cart-status-url="{{ route('cart.status') }}">
        <div class="container">
            <div id="cartRemoteUpdateBanner" class="alert alert-info d-none">Your cart was updated from another device.</div>
            @if ($cart_expired ?? false)
                <div class="alert alert-warning">Your cart expired because it was not ordered within the allowed time.</div>
            @endif
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Checkout</h1>
                    <p class="text-muted mb-0">Choose a delivery slot and payment option.</p>
                </div>
                <a href="{{ route('cart.show') }}" class="btn btn-outline-secondary">Back to Cart</a>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    @if ($pending_order)
                        <div class="alert alert-light border d-flex flex-wrap justify-content-between gap-2">
                            <span>Cart Activity Ref: <strong>{{ $pending_order->reference }}</strong></span>
                            <span>Cart reserved until {{ $pending_order->expires_at->format('d M Y, h:i A') }}</span>
                        </div>
                    @endif
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white fw-semibold">Delivery Details</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('checkout.place') }}" class="row g-3" id="checkoutForm"
                                data-razorpay-order-url="{{ route('checkout.razorpay.order') }}"
                                data-razorpay-verify-url="{{ route('checkout.razorpay.verify') }}"
                                data-razorpay-failure-url="{{ route('checkout.razorpay.failure') }}"
                                data-csrf-token="{{ csrf_token() }}">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" class="form-control @error('customer_name') is-invalid @enderror" required>
                                    @error('customer_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number *</label>
                                    <input type="text" name="customer_mobile" value="{{ old('customer_mobile', $customer?->mobile) }}" class="form-control @error('customer_mobile') is-invalid @enderror" required>
                                    @error('customer_mobile')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" value="{{ old('customer_email', $customer?->email) }}" class="form-control @error('customer_email') is-invalid @enderror">
                                    @error('customer_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @if ($customer)
                                    <div class="col-12">
                                        @if ($approvedAddresses->isNotEmpty())
                                            <label class="form-label d-block">Delivery Address</label>
                                            @if ($approvedAddresses->count() > 1)
                                                <div class="d-flex flex-wrap gap-2 mb-3" role="group" aria-label="Approved delivery addresses">
                                                    @foreach ($approvedAddresses as $address)
                                                        <input type="radio"
                                                               class="btn-check js-address-choice"
                                                               name="customer_address_id"
                                                               id="customerAddress{{ $address->id }}"
                                                               value="{{ $address->id }}"
                                                               data-recipient="{{ $address->recipient_name }}"
                                                               data-mobile="{{ $address->mobile }}"
                                                               data-line1="{{ $address->address_line1 }}"
                                                               data-line2="{{ $address->address_line2 }}"
                                                               data-city="{{ $address->city }}"
                                                               data-state="{{ $address->state }}"
                                                               data-pincode="{{ $address->pincode }}"
                                                               data-landmark="{{ $address->landmark }}"
                                                               autocomplete="off"
                                                               @checked((int) old('customer_address_id', $preferredAddress?->id) === (int) $address->id)>
                                                        <label class="btn btn-outline-success rounded-pill px-3" for="customerAddress{{ $address->id }}">
                                                            {{ $address->label ?: 'Address' }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @else
                                                <input type="hidden" name="customer_address_id" value="{{ $preferredAddress->id }}">
                                                <div class="mb-3">
                                                    <span class="badge text-bg-success rounded-pill px-3 py-2">{{ $preferredAddress->label ?: 'Address' }}</span>
                                                </div>
                                            @endif

                                            <div class="border rounded bg-light p-3" id="selectedAddressDisplay">
                                                <div class="fw-semibold" data-address-display="recipient">{{ $preferredAddress->recipient_name }}</div>
                                                <div data-address-display="line1">{{ $preferredAddress->address_line1 }}</div>
                                                <div class="text-muted small" data-address-display="line2">{{ $preferredAddress->address_line2 }}</div>
                                                <div class="text-muted small">
                                                    <span data-address-display="city">{{ $preferredAddress->city }}</span>,
                                                    <span data-address-display="state">{{ $preferredAddress->state }}</span>
                                                    -
                                                    <span data-address-display="pincode">{{ $preferredAddress->pincode }}</span>
                                                </div>
                                                <div class="text-muted small" data-address-display="landmark">{{ $preferredAddress->landmark }}</div>
                                                <div class="text-muted small">Phone: <span data-address-display="mobile">{{ $preferredAddress->mobile }}</span></div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                No approved delivery address is available. Please add an address in
                                                <a href="{{ route('customer.addresses.index') }}" class="alert-link">My Addresses</a>
                                                and wait for admin approval before checkout.
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($customer)
                                    <input type="hidden" name="delivery_address_line1" value="{{ old('delivery_address_line1', $preferredAddress?->address_line1) }}">
                                    <input type="hidden" name="delivery_address_line2" value="{{ old('delivery_address_line2', $preferredAddress?->address_line2) }}">
                                    <input type="hidden" name="delivery_city" value="{{ old('delivery_city', $preferredAddress?->city) }}">
                                    <input type="hidden" name="delivery_state" value="{{ old('delivery_state', $preferredAddress?->state) }}">
                                    <input type="hidden" name="delivery_pincode" value="{{ old('delivery_pincode', $preferredAddress?->pincode) }}">
                                    <input type="hidden" name="delivery_landmark" value="{{ old('delivery_landmark', $preferredAddress?->landmark) }}">
                                    @foreach (['delivery_address_line1', 'delivery_city', 'delivery_state', 'delivery_pincode'] as $field)
                                        @error($field)
                                            <div class="col-12 text-danger small">{{ $message }}</div>
                                        @enderror
                                    @endforeach
                                @else
                                    <div class="col-12">
                                        <label class="form-label">Address Line 1 *</label>
                                        <input type="text" name="delivery_address_line1" value="{{ old('delivery_address_line1') }}" class="form-control @error('delivery_address_line1') is-invalid @enderror" required>
                                        @error('delivery_address_line1')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address Line 2</label>
                                        <input type="text" name="delivery_address_line2" value="{{ old('delivery_address_line2') }}" class="form-control @error('delivery_address_line2') is-invalid @enderror">
                                        @error('delivery_address_line2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City *</label>
                                        <input type="text" name="delivery_city" value="{{ old('delivery_city') }}" class="form-control @error('delivery_city') is-invalid @enderror" required>
                                        @error('delivery_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State *</label>
                                        <input type="text" name="delivery_state" value="{{ old('delivery_state') }}" class="form-control @error('delivery_state') is-invalid @enderror" required>
                                        @error('delivery_state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PIN Code *</label>
                                        <input type="text" name="delivery_pincode" value="{{ old('delivery_pincode') }}" class="form-control @error('delivery_pincode') is-invalid @enderror" required>
                                        @error('delivery_pincode')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Landmark</label>
                                        <input type="text" name="delivery_landmark" value="{{ old('delivery_landmark') }}" class="form-control @error('delivery_landmark') is-invalid @enderror">
                                        @error('delivery_landmark')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Date</label>
                                    <input type="date" id="deliveryDate" name="delivery_date" value="{{ old('delivery_date', $selectedDeliveryDate) }}" min="{{ $minimumDeliveryDate }}" class="form-control @error('delivery_date') is-invalid @enderror">
                                    @error('delivery_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery Slot</label>
                                    <select name="delivery_slot" id="deliverySlot" class="form-select @error('delivery_slot') is-invalid @enderror @error('checkout') is-invalid @enderror">
                                        <option value="">No preference</option>
                                        @foreach ($deliverySlots as $slot)
                                            <option value="{{ $slot->label }}" @selected(old('delivery_slot') === $slot->label)>{{ $slot->label }}</option>
                                        @endforeach
                                    </select>
                                    @error('delivery_slot')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @error('checkout')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Order Notes</label>
                                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Payment Method</label>
                                    @error('payment_method')
                                        <div class="text-danger small mb-2">{{ $message }}</div>
                                    @enderror
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
                                    <button class="btn btn-success btn-lg" type="submit" @disabled(empty($enabledPaymentMethods) || ($customer && $approvedAddresses->isEmpty()))>Place Order</button>
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
                            @if ($coupon_discount > 0)
                                <div class="d-flex justify-content-between text-success">
                                    <span>Coupon {{ $applied_coupon?->code }}</span>
                                    <strong>- Rs. {{ number_format($coupon_discount, 2) }}</strong>
                                </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between h5">
                                <span>Grand Total</span>
                                <strong>Rs. {{ number_format(max(0, $subtotal - $coupon_discount) + $checkoutSettings['delivery_charge'], 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('.js-cart-sync');
        if (! root) return;

        const banner = document.getElementById('cartRemoteUpdateBanner');
        if (sessionStorage.getItem('cartRemoteUpdated') === '1') {
            sessionStorage.removeItem('cartRemoteUpdated');
            banner?.classList.remove('d-none');
        }

        let currentRevision = Number(root.dataset.cartRevision || 0);
        let pendingReload = false;
        const check = async () => {
            if (pendingReload || document.hidden) return;
            const response = await fetch(root.dataset.cartStatusUrl, { headers: { 'Accept': 'application/json' } });
            if (! response.ok) return;
            const status = await response.json();
            if (Number(status.revision || 0) !== currentRevision) {
                pendingReload = true;
                sessionStorage.setItem('cartRemoteUpdated', '1');
                window.location.reload();
            }
        };

        window.addEventListener('focus', check);
        setInterval(check, 15000);
    });
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addressChoices = document.querySelectorAll('.js-address-choice');

            function updateSelectedAddress(choice) {
                if (!choice) {
                    return;
                }

                const fields = {
                    delivery_address_line1: choice.dataset.line1 || '',
                    delivery_address_line2: choice.dataset.line2 || '',
                    delivery_city: choice.dataset.city || '',
                    delivery_state: choice.dataset.state || '',
                    delivery_pincode: choice.dataset.pincode || '',
                    delivery_landmark: choice.dataset.landmark || '',
                };

                Object.entries(fields).forEach(([name, value]) => {
                    const input = document.querySelector(`input[name="${name}"]`);
                    if (input) {
                        input.value = value;
                    }
                });

                const displayValues = {
                    recipient: choice.dataset.recipient || '',
                    mobile: choice.dataset.mobile || '',
                    line1: choice.dataset.line1 || '',
                    line2: choice.dataset.line2 || '',
                    city: choice.dataset.city || '',
                    state: choice.dataset.state || '',
                    pincode: choice.dataset.pincode || '',
                    landmark: choice.dataset.landmark || '',
                };

                Object.entries(displayValues).forEach(([key, value]) => {
                    const target = document.querySelector(`[data-address-display="${key}"]`);
                    if (target) {
                        target.textContent = value;
                    }
                });
            }

            addressChoices.forEach((choice) => {
                choice.addEventListener('change', () => updateSelectedAddress(choice));
            });

            updateSelectedAddress(document.querySelector('.js-address-choice:checked'));
        });
    </script>
@endpush

@if (in_array('razorpay', $enabledPaymentMethods, true))
@push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('checkoutForm');
            if (!form || typeof Razorpay === 'undefined') {
                return;
            }

            form.addEventListener('submit', async function (event) {
                const method = form.querySelector('input[name="payment_method"]:checked')?.value;

                if (method !== 'razorpay') {
                    return;
                }

                event.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton ? submitButton.textContent : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Opening payment...';
                }

                try {
                    const orderResponse = await fetch(form.dataset.razorpayOrderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': form.dataset.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });
                    const orderData = await orderResponse.json();

                    if (!orderResponse.ok) {
                        throw new Error(orderData.message || 'Unable to initiate online payment.');
                    }

                    const razorpay = new Razorpay({
                        key: orderData.key,
                        amount: orderData.amount,
                        currency: orderData.currency,
                        name: orderData.name,
                        description: orderData.description,
                        order_id: orderData.order_id,
                        prefill: orderData.prefill,
                        handler: async function (response) {
                            const verifyResponse = await fetch(form.dataset.razorpayVerifyUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': form.dataset.csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    order_number: orderData.order_number,
                                    razorpay_order_id: response.razorpay_order_id,
                                    razorpay_payment_id: response.razorpay_payment_id,
                                    razorpay_signature: response.razorpay_signature,
                                }),
                            });
                            const verifyData = await verifyResponse.json();

                            if (!verifyResponse.ok) {
                                throw new Error(verifyData.message || 'Payment verification failed.');
                            }

                            window.location.href = verifyData.redirect_url;
                        },
                        modal: {
                            ondismiss: function () {
                                fetch(form.dataset.razorpayFailureUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': form.dataset.csrfToken,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        order_number: orderData.order_number,
                                        razorpay_order_id: orderData.order_id,
                                        reason: 'Customer closed Razorpay checkout.',
                                    }),
                                });
                            },
                        },
                    });

                    razorpay.on('payment.failed', function (response) {
                        fetch(form.dataset.razorpayFailureUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': form.dataset.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                order_number: orderData.order_number,
                                razorpay_order_id: orderData.order_id,
                                reason: response.error?.description || 'Razorpay payment failed.',
                            }),
                        });
                    });

                    razorpay.open();
                } catch (error) {
                    alert(error.message || 'Unable to complete online payment.');
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                }
            });
        });
    </script>
@endpush
@endif
