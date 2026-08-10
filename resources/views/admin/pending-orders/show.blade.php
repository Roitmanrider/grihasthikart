@extends('layouts.admin')

@section('title', 'Pending Order '.$pendingOrder->reference)

@section('admin-content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $pendingOrder->reference }}</h1>
        <div class="text-muted">{{ str_replace('_', ' ', $pendingOrder->status) }}</div>
    </div>
    <a href="{{ route('admin.pending-orders.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Pending Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt><dd class="col-sm-8">{{ $pendingOrder->customer?->name }} / {{ $pendingOrder->customer?->mobile }}</dd>
                    <dt class="col-sm-4">Started</dt><dd class="col-sm-8">{{ $pendingOrder->started_at->format('d M Y, h:i A') }}</dd>
                    <dt class="col-sm-4">Expires</dt><dd class="col-sm-8">{{ $pendingOrder->expires_at->format('d M Y, h:i A') }}</dd>
                    <dt class="col-sm-4">Reminder</dt><dd class="col-sm-8">{{ $pendingOrder->reminder_sent_at?->format('d M Y, h:i A') ?? 'Not sent' }}</dd>
                    <dt class="col-sm-4">Closed</dt><dd class="col-sm-8">{{ $pendingOrder->closed_at?->format('d M Y, h:i A') ?? '-' }}</dd>
                    <dt class="col-sm-4">Close Reason</dt><dd class="col-sm-8">{{ $pendingOrder->close_reason ? str_replace('_', ' ', $pendingOrder->close_reason) : '-' }}</dd>
                    <dt class="col-sm-4">Converted Order</dt>
                    <dd class="col-sm-8">
                        @if ($pendingOrder->convertedOrder)
                            <a href="{{ route('admin.orders.show', $pendingOrder->convertedOrder) }}">{{ $pendingOrder->convertedOrder->order_number }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Item Snapshot</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Variant / SKU</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Sale Type</th>
                    <th>Added</th>
                    <th>Removed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingOrder->items as $item)
                    <tr>
                        <td>{{ $item->product_name_snapshot }}</td>
                        <td>{{ $item->variant_name_snapshot }} / {{ $item->sku_snapshot }}</td>
                        <td>{{ (float) $item->quantity }}</td>
                        <td>Rs. {{ number_format((float) $item->price_snapshot, 2) }}</td>
                        <td>{{ str_replace('_', ' ', $item->sale_type) }}</td>
                        <td>{{ $item->added_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $item->removed_at?->format('d M Y, h:i A') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No item snapshot found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
