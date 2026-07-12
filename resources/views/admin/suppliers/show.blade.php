@extends('layouts.admin')

@section('title', 'Supplier Details')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $supplier->name }}</h1>
        <div class="text-muted">{{ $supplier->contact_person ?: 'No contact person' }}</div>
    </div>
    <div class="btn-group">
        <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-success">Edit</a>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Basic Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8"><span class="badge {{ $supplier->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($supplier->status) }}</span></dd>
                    <dt class="col-sm-4">Mobile</dt>
                    <dd class="col-sm-8">{{ $supplier->mobile ?: 'Not set' }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $supplier->email ?: 'Not set' }}</dd>
                    <dt class="col-sm-4">GSTIN</dt>
                    <dd class="col-sm-8">{{ $supplier->gstin ?: 'Not set' }}</dd>
                    <dt class="col-sm-4">Address</dt>
                    <dd class="col-sm-8">{{ $supplier->address ?: 'Not set' }}</dd>
                    <dt class="col-sm-4">City/State</dt>
                    <dd class="col-sm-8">{{ collect([$supplier->city, $supplier->state])->filter()->join(', ') ?: 'Not set' }}</dd>
                    <dt class="col-sm-4">Pincode</dt>
                    <dd class="col-sm-8">{{ $supplier->pincode ?: 'Not set' }}</dd>
                    <dt class="col-sm-4">Opening</dt>
                    <dd class="col-sm-8">Rs. {{ number_format((float) $supplier->opening_balance, 2) }}</dd>
                    <dt class="col-sm-4">Purchases</dt>
                    <dd class="col-sm-8">{{ $supplier->purchase_entries_count }}</dd>
                    <dt class="col-sm-4">Notes</dt>
                    <dd class="col-sm-8">{{ $supplier->notes ?: 'Not set' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Filtered Purchase Totals</div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.suppliers.show', $supplier) }}" class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <option value="">All years</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-success btn-sm">Filter</button>
                        <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>
                <div class="row g-3">
                    <div class="col-sm-4"><div class="text-muted small">Purchases</div><div class="fw-semibold">{{ $summary['purchase_count'] }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Purchase Amount</div><div class="fw-semibold">Rs. {{ number_format($summary['purchase_total'], 2) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Freight Allocation</div><div class="fw-semibold">Rs. {{ number_format($summary['freight_total'], 2) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Discount</div><div class="fw-semibold">Rs. {{ number_format($summary['discount_total'], 2) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">CGST</div><div class="fw-semibold">Rs. {{ number_format($summary['cgst_total'], 2) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">SGST</div><div class="fw-semibold">Rs. {{ number_format($summary['sgst_total'], 2) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">GST</div><div class="fw-semibold">Rs. {{ number_format($summary['gst_total'], 2) }}</div></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Recent Purchases</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Purchase</th><th>Date</th><th>Items</th><th>Total</th><th>Freight</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($recentPurchases as $purchase)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $purchase->purchase_number }}</div>
                                    <div class="small text-muted">{{ $purchase->bill_number ?: 'No bill number' }}</div>
                                </td>
                                <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>
                                <td>{{ $purchase->items_count }}</td>
                                <td>Rs. {{ number_format((float) $purchase->grand_total, 2) }}</td>
                                <td>Rs. {{ number_format((float) $purchase->freight_allocation, 2) }}</td>
                                <td class="text-end"><a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No purchase entries for this supplier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
