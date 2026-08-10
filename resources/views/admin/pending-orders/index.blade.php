@extends('layouts.admin')

@section('title', 'Pending Orders')

@section('admin-content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Pending Orders</h1>
        <div class="text-muted">Active, converted, and not ordered cart references.</div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach (['ACTIVE', 'CONVERTED', 'NOT_ORDERED'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Customer / Mobile / Ref</label><input name="search" value="{{ request('search') }}" class="form-control"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-success w-100">Filter</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Pending Ref</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Started At</th>
                    <th>Expires At</th>
                    <th>Remaining Time / Expired</th>
                    <th>Items</th>
                    <th>Cart Value</th>
                    <th>Status</th>
                    <th>Converted Order ID</th>
                    <th>Closed At</th>
                    <th>Close Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingOrders as $pending)
                    @php
                        $value = $pending->activeItems->sum(fn ($item) => (float) $item->quantity * (float) $item->price_snapshot);
                    @endphp
                    <tr>
                        <td><a href="{{ route('admin.pending-orders.show', $pending) }}">{{ $pending->reference }}</a></td>
                        <td>{{ $pending->customer?->name }}</td>
                        <td>{{ $pending->customer?->mobile }}</td>
                        <td>{{ $pending->started_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $pending->expires_at->format('d M Y, h:i A') }}</td>
                        <td>{{ $pending->expires_at->isPast() ? 'Expired' : $pending->expires_at->diffForHumans(null, true).' left' }}</td>
                        <td>{{ $pending->active_items_count }}</td>
                        <td>Rs. {{ number_format($value, 2) }}</td>
                        <td><span class="badge text-bg-secondary">{{ str_replace('_', ' ', $pending->status) }}</span></td>
                        <td>
                            @if ($pending->convertedOrder)
                                <a href="{{ route('admin.orders.show', $pending->convertedOrder) }}">{{ $pending->convertedOrder->order_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $pending->closed_at?->format('d M Y, h:i A') ?? '-' }}</td>
                        <td>{{ $pending->close_reason ? str_replace('_', ' ', $pending->close_reason) : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="text-center text-muted py-5">No pending orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $pendingOrders->links() }}</div>
</div>
@endsection
