@extends('layouts.frontend')

@section('title', 'Order Placed - GrihasthiKart')
@section('description', 'Your GrihasthiKart order has been placed.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-success fw-semibold mb-2">Order placed successfully</div>
                    <h1 class="h3">Order {{ $order->order_number }}</h1>
                    <p class="text-muted mb-1">Payment method: {{ strtoupper($order->payment_method) }}</p>
                    <p class="text-muted">Payment status: {{ str_replace('_', ' ', $order->payment_status) }}</p>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <div class="row g-4 mt-3">
                        <div class="col-md-6">
                            <h2 class="h5">Delivery Details</h2>
                            <p class="mb-1">{{ $order->customer_name }} / {{ $order->customer_mobile }}</p>
                            <p class="mb-0 text-muted">
                                {{ $order->delivery_address_line1 }},
                                {{ $order->delivery_city }},
                                {{ $order->delivery_state }} -
                                {{ $order->delivery_pincode }}
                            </p>
                            @if ($order->delivery_date || $order->delivery_slot)
                                <p class="small text-muted mt-2">
                                    {{ $order->delivery_date?->format('d M Y') }} {{ $order->delivery_slot }}
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h2 class="h5">Total</h2>
                            <div class="display-6 text-success">Rs. {{ number_format((float) $order->grand_total, 2) }}</div>
                            <div class="text-muted">Status: {{ str_replace('_', ' ', $order->order_status) }}</div>
                        </div>
                    </div>

                    @if ($order->payment_method === 'qr')
                        @php($paymentSettings = app(\App\Domains\Setting\Services\BusinessSettingService::class)->publicPaymentSettings())
                        <div class="card border mt-4">
                            <div class="card-body">
                                <h2 class="h5">QR Payment</h2>
                                @if ($paymentSettings['qr_display_name'])
                                    <div class="fw-semibold">{{ $paymentSettings['qr_display_name'] }}</div>
                                @endif
                                @if ($paymentSettings['qr_upi_id'])
                                    <div class="text-muted">UPI ID: {{ $paymentSettings['qr_upi_id'] }}</div>
                                @endif
                                @if ($paymentSettings['qr_image_path'])
                                    <img src="{{ Storage::url($paymentSettings['qr_image_path']) }}" alt="Payment QR" class="img-fluid border rounded mt-3" style="max-width: 220px;">
                                @endif
                                <form method="POST" action="{{ route('orders.payment-proof.store', $order->order_number) }}" enctype="multipart/form-data" class="row g-3 mt-2">
                                    @csrf
                                    <div class="col-md-6">
                                        <label class="form-label">Reference / UTR</label>
                                        <input type="text" name="qr_reference" class="form-control" value="{{ old('qr_reference') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Proof</label>
                                        <input type="file" name="proof" class="form-control" accept="image/*,.pdf" required>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-success">Upload Proof</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @elseif ($order->payment_method === 'cod')
                        <div class="alert alert-light border mt-4 mb-0">Pay on delivery.</div>
                    @elseif ($order->payment_method === 'razorpay')
                        <div class="alert alert-light border mt-4 mb-0">Online payment has been initiated.</div>
                    @endif

                    <a href="{{ route('products.index') }}" class="btn btn-success mt-4">Continue Shopping</a>
                </div>
            </div>
        </div>
    </section>
@endsection
