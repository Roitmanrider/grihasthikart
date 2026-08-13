@extends('layouts.admin')

@section('title', 'Cart Activity '.$pendingOrder->reference)

@section('admin-content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Cart Activity Monitor</h1>
        <div class="text-muted">Technical reference: {{ $pendingOrder->reference }} · {{ str_replace('_', ' ', $pendingOrder->status) }}</div>
    </div>
    <a href="{{ route('admin.pending-orders.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Activity Summary</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Customer</dt><dd class="col-sm-8">{{ $pendingOrder->customer?->name }} / {{ $pendingOrder->customer?->mobile }}</dd>
                    <dt class="col-sm-4">Started</dt><dd class="col-sm-8">{{ $pendingOrder->started_at->format('d M Y, h:i A') }}</dd>
                    <dt class="col-sm-4">Last Activity</dt><dd class="col-sm-8">{{ $pendingOrder->last_activity_at?->format('d M Y, h:i A') ?? '-' }}</dd>
                    <dt class="col-sm-4">Expires</dt><dd class="col-sm-8">{{ $pendingOrder->expires_at->format('d M Y, h:i A') }}</dd>
                    <dt class="col-sm-4">In-App Reminder</dt><dd class="col-sm-8">{{ $pendingOrder->reminder_sent_at?->format('d M Y, h:i A') ?? 'Not sent' }}</dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8">{{ $pendingOrder->whatsapp_reminder_status ? str_replace('_', ' ', $pendingOrder->whatsapp_reminder_status) : 'Not attempted' }}</dd>
                    <dt class="col-sm-4">Anchor Changes</dt><dd class="col-sm-8">{{ $pendingOrder->anchor_change_count }}</dd>
                    <dt class="col-sm-4">Scarce Stock</dt><dd class="col-sm-8">{{ $pendingOrder->scarce_stock_hold ? 'Yes' : 'No' }}</dd>
                    <dt class="col-sm-4">Risk</dt><dd class="col-sm-8">{{ str_replace('_', ' ', $pendingOrder->risk_level) }}</dd>
                    <dt class="col-sm-4">Follow-up</dt><dd class="col-sm-8">{{ str_replace('_', ' ', $pendingOrder->follow_up_status) }}</dd>
                    <dt class="col-sm-4">Follow-up Eligible</dt><dd class="col-sm-8">{{ $pendingOrder->follow_up_eligible_at?->format('d M Y, h:i A') ?? '-' }}</dd>
                    <dt class="col-sm-4">Assigned</dt><dd class="col-sm-8">{{ $pendingOrder->assignedAdmin?->name ?: ($pendingOrder->assignedAdmin?->email ?: 'Unassigned') }}</dd>
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
            <div class="card-footer bg-white d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.pending-orders.assign', $pendingOrder) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="assigned_admin_user_id" value="{{ auth()->id() }}">
                    <button class="btn btn-sm btn-outline-success">Assign to Me</button>
                </form>
                @if ($pendingOrder->assigned_admin_user_id)
                    <form method="POST" action="{{ route('admin.pending-orders.unassign', $pendingOrder) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Clear Assignment</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Recent Monthly Risk</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>Month</th><th>Risk</th><th>Sessions</th><th>Converted</th><th>Expired</th></tr></thead>
                    <tbody>
                        @forelse ($riskHistory as $risk)
                            <tr>
                                <td>{{ $risk->period_month->format('M Y') }}</td>
                                <td>{{ str_replace('_', ' ', $risk->risk_level) }}</td>
                                <td>{{ $risk->cart_sessions }}</td>
                                <td>{{ $risk->converted_count }}</td>
                                <td>{{ $risk->expired_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No monthly risk marks yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">{{ $pendingOrder->status === 'ACTIVE' ? 'Current Cart Items' : 'Compact Item Snapshot' }}</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Variant / SKU</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Sale Type</th>
                    <th>Available Stock</th>
                </tr>
            </thead>
            <tbody>
                @if ($pendingOrder->status === 'ACTIVE')
                    @forelse ($pendingOrder->cart?->items ?? collect() as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}</td>
                            <td>{{ $item->variant_name_snapshot }} / {{ $item->sku_snapshot }}</td>
                            <td>{{ (float) $item->quantity }}</td>
                            <td>Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>{{ str_replace('_', ' ', $item->sale_type) }}</td>
                            <td>{{ number_format((float) $item->productVariant?->inventories?->sum('available_quantity'), 3) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No live cart items found.</td></tr>
                    @endforelse
                @else
                    @forelse ($pendingOrder->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}</td>
                            <td>{{ $item->variant_name_snapshot }} / {{ $item->sku_snapshot }}</td>
                            <td>{{ (float) $item->quantity }}</td>
                            <td>Rs. {{ number_format((float) $item->price_snapshot, 2) }}</td>
                            <td>{{ str_replace('_', ' ', $item->sale_type) }}</td>
                            <td>-</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No compact item snapshot retained.</td></tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
