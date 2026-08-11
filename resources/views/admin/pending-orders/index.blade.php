@extends('layouts.admin')

@section('title', 'Cart Activity Monitor')

@section('admin-content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Cart Activity Monitor</h1>
        <div class="text-muted">Active carts, follow-up signals, recent abandoned activity, converted carts, and risk monitoring.</div>
    </div>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link {{ request('status', 'ACTIVE') === 'ACTIVE' && ! request('filter') ? 'active' : '' }}" href="{{ route('admin.pending-orders.index') }}">Active Carts</a></li>
    @if ($employeeFollowupEnabled)
        <li class="nav-item"><a class="nav-link {{ request('filter') === 'call_followup' ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['filter' => 'call_followup']) }}">Cart Follow-up</a></li>
    @endif
    <li class="nav-item"><a class="nav-link {{ request('status') === 'NOT_ORDERED' ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['status' => 'NOT_ORDERED']) }}">Recent Abandoned</a></li>
    <li class="nav-item"><a class="nav-link {{ request('status') === 'CONVERTED' ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['status' => 'CONVERTED']) }}">Converted</a></li>
    <li class="nav-item"><a class="nav-link {{ in_array(request('filter'), ['watch', 'high_risk'], true) ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['filter' => 'watch']) }}">Risk Monitoring</a></li>
</ul>

@unless ($employeeFollowupEnabled)
    <div class="alert alert-light border">Employee Cart Follow-up is disabled. Cart reservations, expiry, in-app reminders, WhatsApp reminders, and risk monitoring continue normally.</div>
@endunless

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach (['ACTIVE', 'CONVERTED', 'NOT_ORDERED'] as $status)
                        <option value="{{ $status }}" @selected(request('status', 'ACTIVE') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Filter</label>
                <select name="filter" class="form-select">
                    <option value="">Priority</option>
                    @foreach (['whatsapp_due' => 'WhatsApp Due', 'call_followup' => 'Call Follow-up', 'scarce_stock' => 'Scarce Stock Hold', 'watch' => 'Watch', 'high_risk' => 'High Risk'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('filter') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Customer / Mobile / Ref</label><input name="search" value="{{ request('search') }}" class="form-control"></div>
            <div class="col-md-1 d-flex align-items-end"><button class="btn btn-success w-100">Filter</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Items</th>
                    <th>Reserved Value</th>
                    <th>Last Activity</th>
                    <th>Expires In</th>
                    <th>WhatsApp Status</th>
                    <th>Scarce Stock</th>
                    <th>Risk</th>
                    <th>Follow-up</th>
                    <th>Ref</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingOrders as $pending)
                    @php
                        $liveItems = $pending->status === 'ACTIVE' ? $pending->cart?->items : collect();
                        $itemCount = $pending->status === 'ACTIVE' ? (float) $liveItems->sum('quantity') : $pending->item_count_snapshot;
                        $value = $pending->status === 'ACTIVE'
                            ? $liveItems->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_price)
                            : (float) $pending->cart_value_snapshot;
                    @endphp
                    <tr>
                        <td>{{ $pending->customer?->name }}</td>
                        <td>{{ $pending->customer?->mobile }}</td>
                        <td>{{ $itemCount }}</td>
                        <td>Rs. {{ number_format($value, 2) }}</td>
                        <td>{{ $pending->last_activity_at?->diffForHumans() ?? '-' }}</td>
                        <td>{{ $pending->expires_at->isPast() ? 'Expired' : $pending->expires_at->diffForHumans(null, true).' left' }}</td>
                        <td>{{ $pending->whatsapp_reminder_status ? str_replace('_', ' ', $pending->whatsapp_reminder_status) : ($pending->whatsapp_reminder_due_at ? 'Due '.$pending->whatsapp_reminder_due_at->diffForHumans() : 'Disabled') }}</td>
                        <td><span class="badge {{ $pending->scarce_stock_hold ? 'text-bg-warning' : 'text-bg-light text-dark' }}">{{ $pending->scarce_stock_hold ? 'Yes' : 'No' }}</span></td>
                        <td><span class="badge {{ $pending->risk_level === 'HIGH_RISK' ? 'text-bg-danger' : ($pending->risk_level === 'WATCH' ? 'text-bg-warning' : 'text-bg-success') }}">{{ str_replace('_', ' ', $pending->risk_level) }}</span></td>
                        <td>{{ str_replace('_', ' ', $pending->follow_up_status) }}</td>
                        <td><a href="{{ route('admin.pending-orders.show', $pending) }}">{{ $pending->reference }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center text-muted py-5">No cart activity found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $pendingOrders->links() }}</div>
</div>
@endsection
