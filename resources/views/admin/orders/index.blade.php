@extends('layouts.admin')

@section('title','Orders')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Orders</h1>
        <div class="text-muted">Cash on Delivery orders.</div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Order, customer, mobile">
            </div>
            <div class="col-md-2">
                <label class="form-label">Order Status</label>
                <select name="order_status" class="form-select">
                    <option value="">All</option>
                    @foreach (\App\Models\Order::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">All</option>
                    @foreach (\App\Models\Order::PAYMENT_STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th>Placed</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="fw-semibold">{{ $order->order_number }}</td>
                        <td>
                            <div>{{ $order->customer_name }}</div>
                            <div class="small text-muted">{{ $order->customer_mobile }}</div>
                        </td>
                        <td><span class="badge text-bg-light border">{{ str_replace('_', ' ', $order->order_status) }}</span></td>
                        <td>{{ strtoupper($order->payment_method) }} / {{ $order->payment_status }}</td>
                        <td>Rs. {{ number_format((float) $order->grand_total, 2) }}</td>
                        <td>{{ $order->placed_at?->format('d M Y, h:i A') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-success">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $orders->links() }}</div>
</div>

@endsection
