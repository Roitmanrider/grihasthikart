@extends('layouts.admin')

@section('title', 'Stock Adjustments')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Stock Adjustments</h1>
        <div class="text-muted">Manual stock corrections and physical count adjustments.</div>
    </div>
    <a href="{{ route('admin.stock-adjustments.create') }}" class="btn btn-success">New Adjustment</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Variant</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Before</th>
                    <th>After</th>
                    <th>Reason</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stockAdjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->adjustment_date?->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $adjustment->productVariant?->product?->name }}</div>
                            <div class="small text-muted">{{ $adjustment->productVariant?->variant_name }} / {{ $adjustment->productVariant?->sku }}</div>
                        </td>
                        <td><span class="badge text-bg-light border">{{ str($adjustment->adjustment_type)->headline() }}</span></td>
                        <td>{{ number_format((float) $adjustment->quantity, 3) }}</td>
                        <td>{{ number_format((float) $adjustment->before_quantity, 3) }}</td>
                        <td>{{ number_format((float) $adjustment->after_quantity, 3) }}</td>
                        <td>{{ str($adjustment->reason)->replace('_', ' ')->headline() }}</td>
                        <td class="text-end"><a href="{{ route('admin.stock-adjustments.show', $adjustment) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No stock adjustments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $stockAdjustments->links() }}</div>
</div>
@endsection
