@extends('layouts.frontend')

@section('title', 'Cart - GrihasthiKart')
@section('description', 'Review your GrihasthiKart cart.')

@section('content')
    <section class="py-5 js-cart-sync" data-cart-revision="{{ $cart->revision }}" data-cart-status-url="{{ route('cart.status') }}">
        <div class="container">
            <div id="cartRemoteUpdateBanner" class="alert alert-info d-none">Your cart was updated from another device.</div>
            @if ($cart_expired ?? false)
                <div class="alert alert-warning">Your cart expired because it was not ordered within the allowed time.</div>
            @endif
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
                @if ($errors->has('cart'))
                    <div class="alert alert-danger">{{ $errors->first('cart') }}</div>
                @endif
                @if ($cart->items->contains(fn ($item) => $item->sale_type === 'daily_offer' && $item->daily_offer_reserved_until && $item->daily_offer_reserved_until->isPast()))
                    <div class="alert alert-warning">A Daily Offer reservation in your cart has expired. Please remove and re-add the item if the offer is still available.</div>
                @endif
                @if ($pending_order)
                    <div class="alert alert-light border d-flex flex-wrap justify-content-between gap-2">
                        <span>Cart Activity Ref: <strong>{{ $pending_order->reference }}</strong></span>
                        <span>Cart reserved until {{ $pending_order->expires_at->format('d M Y, h:i A') }}</span>
                    </div>
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
                                                $imageUrl = app(\App\Services\MediaResolver::class)->productImageUrl($variant?->product, $variant);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex gap-3">
                                                        @if ($imageUrl)
                                                            <img src="{{ $imageUrl }}" class="rounded object-fit-cover" style="width: 72px; height: 72px;" alt="{{ $item->product_name_snapshot }}">
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
                                                            @if ($item->sale_type === 'daily_offer')
                                                                <div class="small {{ $item->daily_offer_reserved_until?->isPast() ? 'text-danger' : 'text-success' }}"
                                                                     data-daily-offer-countdown
                                                                     data-expires-at="{{ $item->daily_offer_reserved_until?->toIso8601String() }}">
                                                                    @if ($item->daily_offer_reserved_until?->isPast())
                                                                        Daily Offer reservation expired
                                                                    @else
                                                                        Daily Offer price reserved until {{ $item->daily_offer_reserved_until?->format('h:i A') }}
                                                                    @endif
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
                                                    <form method="POST" action="{{ route('cart.items.update', $item) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="d-flex gap-2">
                                                            <input type="number" name="quantity" value="{{ (int) $item->quantity }}" min="1" step="1" class="form-control form-control-sm @error('quantity') is-invalid @enderror">
                                                            <button class="btn btn-sm btn-outline-success">Update</button>
                                                        </div>
                                                        @error('quantity')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
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
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Delivery Charge</span>
                                    <span class="fw-semibold">{{ (float) $delivery_charge > 0 ? 'Rs. '.number_format((float) $delivery_charge, 2) : 'Free' }}</span>
                                </div>
                                @if (($delivery_rule['free_delivery_remaining'] ?? 0) > 0)
                                    <div class="small text-success mb-3">Add Rs. {{ number_format((float) $delivery_rule['free_delivery_remaining'], 2) }} more for FREE delivery</div>
                                @elseif (($delivery_rule['free_delivery_threshold'] ?? null) !== null)
                                    <div class="small text-success mb-3">Free delivery unlocked</div>
                                @endif
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
                                        <form method="POST" action="{{ route('cart.coupon.apply') }}">
                                            @csrf
                                            <div class="d-flex gap-2">
                                                <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror @error('coupon') is-invalid @enderror" placeholder="Coupon code">
                                                <button class="btn btn-outline-success">Apply</button>
                                            </div>
                                            @error('code')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            @error('coupon')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </form>
                                    @endif
                                </div>
                                @if ($coupon_discount > 0)
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Coupon Discount</span>
                                        <span class="fw-semibold text-success">- Rs. {{ number_format($coupon_discount, 2) }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between h5 mb-3">
                                    <span>Grand Total</span>
                                    <span>Rs. {{ number_format($grand_total, 2) }}</span>
                                </div>
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

        const countdowns = document.querySelectorAll('[data-daily-offer-countdown]');
        const pad = (value) => String(value).padStart(2, '0');
        const renderCountdowns = () => {
            countdowns.forEach((node) => {
                const expiresAt = Date.parse(node.dataset.expiresAt || '');
                if (! expiresAt) return;
                const remaining = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
                if (remaining <= 0) {
                    node.textContent = 'Daily Offer reservation expired';
                    node.classList.remove('text-success');
                    node.classList.add('text-danger');
                    return;
                }
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                node.textContent = `Daily Offer price reserved for ${pad(minutes)}:${pad(seconds)}`;
            });
        };
        renderCountdowns();
        setInterval(renderCountdowns, 1000);
    });
    </script>
@endsection
