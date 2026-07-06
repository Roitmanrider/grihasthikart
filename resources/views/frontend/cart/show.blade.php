@extends('layouts.frontend')

@section('title', 'Cart - GrihasthiKart')
@section('description', 'Review your GrihasthiKart cart.')

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">Cart</h1>
                    <p class="text-muted mb-0">Prices and tax details are captured as item snapshots.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-success">Continue Shopping</a>
            </div>

            @if ($cart->items->isNotEmpty())
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Price</th>
                                            <th style="width: 170px;">Quantity</th>
                                            <th>Line Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cart->items as $item)
                                            @php
                                                $variant = $item->productVariant;
                                                $image = $variant?->primaryImage?->path ?? $variant?->product?->primaryImage?->path;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex gap-3">
                                                        @if ($image)
                                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}" class="rounded object-fit-cover" style="width: 72px; height: 72px;" alt="{{ $item->product_name_snapshot }}">
                                                        @else
                                                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-success" style="width: 72px; height: 72px;">
                                                                <i class="fa-solid fa-basket-shopping"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                                            <div class="text-muted small">{{ $item->variant_name_snapshot }} / {{ $item->sku_snapshot }}</div>
                                                            @if ($item->attributes_snapshot)
                                                                <div class="small text-muted">
                                                                    @foreach ($item->attributes_snapshot as $attribute)
                                                                        <span>{{ $attribute['attribute'] }}: {{ $attribute['value'] }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">Rs. {{ number_format((float) $item->unit_price, 2) }}</div>
                                                    @if ($item->mrp > $item->unit_price)
                                                        <div class="small text-muted text-decoration-line-through">Rs. {{ number_format((float) $item->mrp, 2) }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <form method="POST" action="{{ route('cart.items.update', $item) }}" class="d-flex gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="number" name="quantity" value="{{ (int) $item->quantity }}" min="1" step="1" class="form-control form-control-sm">
                                                        <button class="btn btn-sm btn-outline-success">Update</button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">Rs. {{ number_format($item->line_total, 2) }}</div>
                                                    @if ($item->line_savings > 0)
                                                        <div class="small text-success">Saved Rs. {{ number_format($item->line_savings, 2) }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white fw-semibold">Cart Summary</div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span class="fw-semibold">Rs. {{ number_format($subtotal, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Total Savings</span>
                                    <span class="fw-semibold text-success">Rs. {{ number_format($savings, 2) }}</span>
                                </div>
                                <div class="border-top pt-3 mb-3">
                                    @if ($applied_coupon)
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <div class="fw-semibold">{{ $applied_coupon->code }}</div>
                                                <div class="small text-muted">Coupon discount: Rs. {{ number_format($coupon_discount, 2) }}</div>
                                            </div>
                                            <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="text" name="code" class="form-control" placeholder="Coupon code">
                                            <button class="btn btn-outline-success">Apply</button>
                                        </form>
                                    @endif
                                </div>
                                @if ($coupon_discount > 0)
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Coupon Discount</span>
                                        <span class="fw-semibold text-success">- Rs. {{ number_format($coupon_discount, 2) }}</span>
                                    </div>
                                @endif
                                <a href="{{ route('checkout.show') }}" class="btn btn-success w-100">Checkout</a>
                                <form method="POST" action="{{ route('cart.clear') }}" class="mt-2">
                                    @csrf
                                    <button class="btn btn-outline-danger w-100" type="submit">Clear Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-light border">
                    Your cart is empty.
                    <a href="{{ route('products.index') }}" class="alert-link">Browse products</a>
                </div>
            @endif
        </div>
    </section>
@endsection
