@extends('layouts.admin')

@section('title', 'Purchase Details')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $purchase->purchase_number }}</h1>
        <div class="text-muted">{{ $purchase->purchase_date?->format('d M Y') }} / {{ $purchase->bill_number ?: 'No bill number' }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.purchases.print', $purchase) }}" class="btn btn-outline-success" target="_blank">Print</a>
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Purchase Items</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>GST</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->productVariant?->product?->name }}</div>
                                    <div class="small text-muted">{{ $item->productVariant?->variant_name }} / {{ $item->sku }}</div>
                                    @if ($item->batch_number || $item->expiry_date)
                                        <div class="small text-muted">Batch: {{ $item->batch_number ?: 'N/A' }} / Exp: {{ $item->expiry_date?->format('d M Y') ?: 'N/A' }}</div>
                                    @endif
                                </td>
                                <td>{{ number_format((float) $item->quantity, 3) }}</td>
                                <td>Rs. {{ number_format((float) $item->purchase_price, 2) }}</td>
                                <td>{{ number_format((float) $item->gst_rate, 2) }}% / Rs. {{ number_format((float) $item->gst_amount, 2) }}</td>
                                <td>Rs. {{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Supplier</span><span>{{ $purchase->supplier_id ? '#'.$purchase->supplier_id : 'Not recorded' }}</span></div>
                <div class="d-flex justify-content-between"><span>Status</span><span class="badge text-bg-success">{{ str($purchase->status)->headline() }}</span></div>
                <hr>
                <div class="d-flex justify-content-between"><span>Subtotal</span><strong>Rs. {{ number_format((float) $purchase->subtotal, 2) }}</strong></div>
                <div class="d-flex justify-content-between"><span>GST</span><span>Rs. {{ number_format((float) $purchase->gst_total, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Discount</span><span>Rs. {{ number_format((float) $purchase->discount_total, 2) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between h5"><span>Grand Total</span><strong>Rs. {{ number_format((float) $purchase->grand_total, 2) }}</strong></div>
                @if ($purchase->notes)
                    <div class="text-muted small mt-3">{{ $purchase->notes }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
