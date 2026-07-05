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
                    <p class="text-muted">Payment method: Cash on Delivery</p>

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

                    <a href="{{ route('products.index') }}" class="btn btn-success mt-4">Continue Shopping</a>
                </div>
            </div>
        </div>
    </section>
@endsection
