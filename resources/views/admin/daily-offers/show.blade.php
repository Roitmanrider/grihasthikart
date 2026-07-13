@extends('layouts.admin')

@section('title', 'Daily Offer Details')

@section('admin-content')
    @php
        $variant = $dailyOffer->productVariant;
        $product = $variant?->product;
        $availableStock = (float) $variant?->inventories?->sum('available_quantity');
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Daily Offer Details</h1>
            <div class="text-muted">{{ $product?->name }} / {{ $variant?->variant_name }} / {{ $variant?->sku }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.daily-offers.edit', $dailyOffer) }}" class="btn btn-success">Edit</a>
            <a href="{{ route('admin.daily-offers.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted">Product</div>
                    <div class="fw-semibold">{{ $product?->name ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">Variant</div>
                    <div class="fw-semibold">{{ $variant?->variant_name ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted">SKU</div>
                    <div class="fw-semibold">{{ $variant?->sku ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Normal Selling Price</div>
                    <div class="fw-semibold">Rs. {{ number_format((float) ($variant?->selling_price ?? 0), 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Offer Price</div>
                    <div class="fw-semibold">Rs. {{ number_format((float) $dailyOffer->offer_price, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Discount</div>
                    <div class="fw-semibold">Rs. {{ number_format($dailyOffer->discountAmount(), 2) }} ({{ number_format($dailyOffer->discountPercentage(), 2) }}%)</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Available Stock</div>
                    <div class="fw-semibold">{{ number_format($availableStock, 0) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Lifecycle</div>
                    <span class="badge {{ $dailyOffer->lifecycleBadgeClass() }}">{{ $dailyOffer->lifecycleState() }}</span>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Remaining Time</div>
                    <div class="fw-semibold">{{ $dailyOffer->remainingTimeLabel() }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Starts At</div>
                    <div class="fw-semibold">{{ $dailyOffer->starts_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: 'Anytime' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="small text-muted">Ends At</div>
                    <div class="fw-semibold">{{ $dailyOffer->ends_at?->timezone(config('app.timezone'))->format('d M Y, h:i A') ?: 'Open' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
