@extends('layouts.admin')

@section('title', 'Coupon Details')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $coupon->code }}</h1>
        <div class="text-muted">{{ $coupon->name }}</div>
    </div>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Summary</div>
            <div class="card-body">
                <div class="mb-2">Discount: <strong>{{ $coupon->discount_type }} / {{ number_format((float) $coupon->discount_value, 2) }}</strong></div>
                <div class="mb-2">Minimum order: Rs. {{ number_format((float) $coupon->minimum_order_amount, 2) }}</div>
                <div class="mb-2">Usage count: {{ $coupon->usages_count }}</div>
                <div>Status: {{ $coupon->status ? 'Active' : 'Inactive' }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Usage History</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Order</th><th>Customer</th><th>Discount</th><th>Used At</th></tr></thead>
                    <tbody>
                        @forelse ($coupon->usages as $usage)
                            <tr>
                                <td>{{ $usage->order?->order_number ?: '-' }}</td>
                                <td>{{ $usage->customer_id ?: $usage->session_id }}</td>
                                <td>Rs. {{ number_format((float) $usage->discount_amount, 2) }}</td>
                                <td>{{ $usage->used_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No usage yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
