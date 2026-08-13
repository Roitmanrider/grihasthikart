@extends('layouts.admin')

@section('title', 'Cart Activity Monitor')

@section('admin-content')
@php
    $activeFilters = collect($filters ?? []);
    $quickCards = [
        'call_followup' => ['label' => 'Need Follow-up', 'count' => $quickCounts['need_follow_up'] ?? 0],
        'expiring_soon' => ['label' => 'Expiring Soon', 'count' => $quickCounts['expiring_soon'] ?? 0],
        'scarce_stock' => ['label' => 'Scarce Stock Holds', 'count' => $quickCounts['scarce_stock'] ?? 0],
        'high_risk' => ['label' => 'High Risk', 'count' => $quickCounts['high_risk'] ?? 0],
        'premium' => ['label' => 'Premium', 'count' => $quickCounts['premium'] ?? 0],
        'whatsapp_failed' => ['label' => 'WhatsApp Failed', 'count' => $quickCounts['whatsapp_failed'] ?? 0],
        'unassigned' => ['label' => 'Unassigned', 'count' => $quickCounts['unassigned'] ?? 0],
    ];
    $filterOptions = [
        'expiring_soon' => 'Expiring Soon',
        'scarce_stock' => 'Scarce Stock Hold',
        'high_risk' => 'High Risk',
        'watch' => 'Watch',
        'high_cart_value' => 'High Cart Value',
        'oldest_waiting' => 'Oldest Waiting',
        'premium' => 'Premium Customer',
        'whatsapp_due' => 'WhatsApp Due',
        'whatsapp_sent' => 'WhatsApp Sent',
        'whatsapp_failed' => 'WhatsApp Failed',
        'not_contacted' => 'Not Contacted',
        'called' => 'Called',
        'no_answer' => 'No Answer',
        'will_order' => 'Will Order',
        'not_interested' => 'Not Interested',
        'daily_offer' => 'Daily Offer in Cart',
        'unassigned' => 'Unassigned',
        'assigned_me' => 'Assigned to Me',
    ];
    $sortOptions = [
        'expires_soonest' => 'Expires Soonest',
        'highest_cart_value' => 'Highest Cart Value',
        'oldest_waiting' => 'Oldest Waiting',
        'highest_risk' => 'Highest Risk',
        'premium_first' => 'Premium First',
        'most_scarce_stock' => 'Most Scarce Stock',
        'most_recently_active' => 'Most Recently Active',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Cart Activity Monitor</h1>
        <div class="text-muted">Active carts, follow-up queue, recent abandoned activity, converted carts, and risk monitoring.</div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link {{ request('status', 'ACTIVE') === 'ACTIVE' && ! $activeFilters->contains('call_followup') ? 'active' : '' }}" href="{{ route('admin.pending-orders.index') }}">Active Carts</a></li>
    @if ($employeeFollowupEnabled)
        <li class="nav-item"><a class="nav-link {{ $activeFilters->contains('call_followup') ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['filters' => ['call_followup']]) }}">Cart Follow-up</a></li>
    @endif
    <li class="nav-item"><a class="nav-link {{ request('status') === 'NOT_ORDERED' ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['status' => 'NOT_ORDERED']) }}">Recent Abandoned</a></li>
    <li class="nav-item"><a class="nav-link {{ request('status') === 'CONVERTED' ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['status' => 'CONVERTED']) }}">Converted</a></li>
    <li class="nav-item"><a class="nav-link {{ $activeFilters->intersect(['watch', 'high_risk'])->isNotEmpty() ? 'active' : '' }}" href="{{ route('admin.pending-orders.index', ['filters' => ['call_followup', 'watch']]) }}">Risk Monitoring</a></li>
</ul>

@unless ($employeeFollowupEnabled)
    <div class="alert alert-light border">Employee Cart Follow-up is disabled. Cart reservations, expiry, in-app reminders, WhatsApp reminders, and risk monitoring continue normally.</div>
@endunless

<div class="row g-3 mb-4">
    @foreach ($quickCards as $filter => $card)
        <div class="col-6 col-md-3 col-xl">
            <a class="text-decoration-none" href="{{ route('admin.pending-orders.index', ['filters' => array_values(array_unique(array_merge(['call_followup'], [$filter]))), 'sort' => request('sort', 'expires_soonest')]) }}">
                <div class="border rounded bg-white p-3 h-100">
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <div class="h4 mb-0 text-dark">{{ $card['count'] }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

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
            <div class="col-md-3">
                <label class="form-label">Filters</label>
                <select name="filters[]" class="form-select" multiple size="6">
                    @foreach ($filterOptions as $value => $label)
                        <option value="{{ $value }}" @selected($activeFilters->contains($value))>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Assigned Employee</label>
                <select name="assigned_admin_user_id" class="form-select">
                    <option value="">Any</option>
                    @foreach ($assignableAdmins as $admin)
                        <option value="{{ $admin->id }}" @selected((int) request('assigned_admin_user_id') === (int) $admin->id)>{{ $admin->name ?: $admin->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sort By</label>
                <select name="sort" class="form-select">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('sort', 'expires_soonest') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Customer / Mobile / Ref</label><input name="search" value="{{ request('search') }}" class="form-control"></div>
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
                    <th>Items</th>
                    <th>Reserved Value</th>
                    <th>Last Activity</th>
                    <th>Expires In</th>
                    <th>WhatsApp</th>
                    <th>Risk</th>
                    <th>Follow-up</th>
                    <th>Assigned</th>
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
                        $callAvailable = $employeeFollowupEnabled && $pending->status === 'ACTIVE' && $pending->follow_up_eligible_at && $pending->expires_at->isFuture();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $pending->customer?->name }}</div>
                            <div class="small text-muted">{{ $pending->customer?->mobile }}</div>
                            @if ($pending->customer?->is_premium)
                                <span class="badge text-bg-success">Premium</span>
                            @endif
                        </td>
                        <td>{{ $itemCount }}</td>
                        <td>Rs. {{ number_format($value, 2) }}</td>
                        <td>{{ $pending->last_activity_at?->diffForHumans() ?? '-' }}</td>
                        <td>{{ $pending->expires_at->isPast() ? 'Expired' : $pending->expires_at->diffForHumans(null, true).' left' }}</td>
                        <td>{{ $pending->whatsapp_reminder_status ? str_replace('_', ' ', $pending->whatsapp_reminder_status) : ($pending->whatsapp_reminder_due_at ? 'Due '.$pending->whatsapp_reminder_due_at->diffForHumans() : 'Disabled') }}</td>
                        <td>
                            <span class="badge {{ $pending->risk_level === 'HIGH_RISK' ? 'text-bg-danger' : ($pending->risk_level === 'WATCH' ? 'text-bg-warning' : 'text-bg-success') }}">{{ str_replace('_', ' ', $pending->risk_level) }}</span>
                            @if ($pending->scarce_stock_hold)
                                <span class="badge text-bg-warning">Scarce</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.pending-orders.follow-up', $pending) }}" class="d-flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="follow_up_status" class="form-select form-select-sm" @disabled(! $callAvailable)>
                                    @foreach (['NOT_CONTACTED', 'CALLED', 'WILL_ORDER', 'NO_ANSWER', 'NOT_INTERESTED', 'WATCH_CUSTOMER'] as $status)
                                        <option value="{{ $status }}" @selected($pending->follow_up_status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-success" @disabled(! $callAvailable)>Save</button>
                            </form>
                            <div class="small text-muted mt-1">{{ $pending->follow_up_eligible_at ? 'Waiting since '.$pending->follow_up_eligible_at->diffForHumans() : 'Not eligible yet' }}</div>
                        </td>
                        <td>
                            <div class="small">{{ $pending->assignedAdmin?->name ?: ($pending->assignedAdmin?->email ?: 'Unassigned') }}</div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <form method="POST" action="{{ route('admin.pending-orders.assign', $pending) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="assigned_admin_user_id" class="form-select form-select-sm">
                                        @foreach ($assignableAdmins as $admin)
                                            <option value="{{ $admin->id }}" @selected((int) $pending->assigned_admin_user_id === (int) $admin->id)>{{ $admin->name ?: $admin->email }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary">Assign</button>
                                </form>
                                @if ($pending->assigned_admin_user_id)
                                    <form method="POST" action="{{ route('admin.pending-orders.unassign', $pending) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Clear</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                        <td><a href="{{ route('admin.pending-orders.show', $pending) }}">{{ $pending->reference }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-5">No cart activity found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $pendingOrders->links() }}</div>
</div>
@endsection
