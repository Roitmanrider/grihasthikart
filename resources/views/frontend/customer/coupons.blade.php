@extends('layouts.frontend')

@section('title', 'My Coupons')

@section('content')
<section class="py-5">
    <div class="container">
        @include('frontend.customer.account-nav')

        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">My Coupons</h1>
                <div class="text-muted">Only one coupon can be applied per order.</div>
            </div>
            <a href="{{ route('cart.show') }}" class="btn btn-outline-success">Apply in Cart</a>
        </div>

        <div class="row g-4">
            @forelse ($coupons as $coupon)
                @php
                    $status = ! $coupon->status
                        ? 'Inactive'
                        : ($coupon->starts_at && $coupon->starts_at->isFuture()
                            ? 'Not Yet Active'
                            : ($coupon->expires_at && $coupon->expires_at->isPast() ? 'Expired' : 'Available'));
                    $statusClass = $status === 'Available' ? 'text-bg-success' : 'text-bg-secondary';
                    $discount = match ($coupon->purpose) {
                        \App\Models\Coupon::PURPOSE_FREE_DELIVERY => 'Free Delivery',
                        \App\Models\Coupon::PURPOSE_DELIVERY_FIXED => 'Rs. '.number_format((float) $coupon->discount_value, 0).' off Delivery',
                        \App\Models\Coupon::PURPOSE_DELIVERY_PERCENT => number_format((float) $coupon->discount_value, 0).'% off Delivery',
                        default => $coupon->discount_type === 'percentage'
                            ? number_format((float) $coupon->discount_value, 0).'% off eligible merchandise'
                            : 'Rs. '.number_format((float) $coupon->discount_value, 0).' off eligible merchandise',
                    };
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <div class="fw-semibold">{{ $coupon->code }}</div>
                                <span class="badge {{ $statusClass }} rounded-pill px-3 py-2">{{ $status }}</span>
                            </div>
                            <div class="h6 mb-2">{{ $coupon->name }}</div>
                            <div class="text-success fw-semibold">{{ $discount }}</div>
                            @if ($coupon->description)
                                <div class="text-muted small mt-2">{{ $coupon->description }}</div>
                            @endif
                            <div class="small text-muted mt-3">
                                @if ((float) $coupon->minimum_order_amount > 0)
                                    Minimum order Rs. {{ number_format((float) $coupon->minimum_order_amount, 0) }}.
                                @endif
                                @if ($coupon->expires_at)
                                    Valid until {{ $coupon->expires_at->format('d M Y') }}.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border">No coupons are available for your account right now.</div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $coupons->links() }}</div>
    </div>
</section>
@endsection
