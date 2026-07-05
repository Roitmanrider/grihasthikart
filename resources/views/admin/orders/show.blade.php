@extends('layouts.admin')

@section('title','Order Details')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $order->order_number }}</h1>
        <div class="text-muted">{{ $order->customer_name }} / {{ $order->customer_mobile }}</div>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Order Items</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                    <div class="small text-muted">{{ $item->variant_name_snapshot }} / {{ $item->sku_snapshot }}</div>
                                    <div class="small text-muted">GST: {{ $item->gst_rate_snapshot ?? 0 }}%</div>
                                </td>
                                <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                                <td>Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                                <td>Rs. {{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Status History</div>
            <div class="card-body">
                @forelse ($order->statusHistories as $history)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">{{ $history->old_status ?? 'New' }} -> {{ $history->new_status }}</div>
                        <div class="small text-muted">{{ $history->note ?? 'No note' }} / {{ $history->created_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @empty
                    <div class="text-muted">No status history.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Summary</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Subtotal</span><strong>Rs. {{ number_format((float) $order->subtotal, 2) }}</strong></div>
                <div class="d-flex justify-content-between"><span>MRP Total</span><span>Rs. {{ number_format((float) $order->total_mrp, 2) }}</span></div>
                <div class="d-flex justify-content-between text-success"><span>Savings</span><span>Rs. {{ number_format((float) $order->total_savings, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Tax</span><span>Rs. {{ number_format((float) $order->tax_total, 2) }}</span></div>
                @if ($order->discount_total > 0)
                    <div class="d-flex justify-content-between text-success"><span>Coupon {{ $order->coupon_code_snapshot }}</span><span>- Rs. {{ number_format((float) $order->discount_total, 2) }}</span></div>
                @endif
                <hr>
                <div class="d-flex justify-content-between h5"><span>Grand Total</span><strong>Rs. {{ number_format((float) $order->grand_total, 2) }}</strong></div>
                <div class="mt-3"><span class="badge text-bg-light border">{{ str_replace('_', ' ', $order->order_status) }}</span></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Delivery</div>
            <div class="card-body">
                <div class="fw-semibold">{{ $order->customer_name }}</div>
                <div>{{ $order->customer_mobile }}</div>
                <div class="text-muted small mt-2">{{ $order->delivery_address_line1 }}, {{ $order->delivery_city }}, {{ $order->delivery_state }} - {{ $order->delivery_pincode }}</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Update Status</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="row g-3">
                    @csrf
                    @method('PATCH')
                    <div class="col-12">
                        <select name="order_status" class="form-select">
                            @foreach (\App\Models\Order::STATUSES as $status)
                                <option value="{{ $status }}" @selected($order->order_status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Admin notes">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success w-100">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
