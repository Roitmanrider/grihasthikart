@extends('layouts.admin')

@section('title', 'Purchases')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Purchases</h1>
        <div class="text-muted">Posted stock inward entries from supplier purchases.</div>
    </div>
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-success">New Purchase</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Purchase</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $purchase->purchase_number }}</div>
                            <div class="small text-muted">{{ $purchase->bill_number ?: 'No bill number' }}</div>
                        </td>
                        <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>
                        <td>
                            @if ($purchase->supplier)
                                <a href="{{ route('admin.suppliers.show', $purchase->supplier) }}" class="text-decoration-none">{{ $purchase->supplier->name }}</a>
                            @elseif ($purchase->supplier_id)
                                #{{ $purchase->supplier_id }}
                            @else
                                Not recorded
                            @endif
                        </td>
                        <td>{{ $purchase->items_count }}</td>
                        <td>Rs. {{ number_format((float) $purchase->grand_total, 2) }}</td>
                        <td><span class="badge text-bg-success">{{ str($purchase->status)->headline() }}</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-outline-secondary">View</a>
                                <a href="{{ route('admin.purchases.print', $purchase) }}" class="btn btn-outline-success" target="_blank">Print</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No purchase entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $purchases->links() }}</div>
</div>
@endsection
