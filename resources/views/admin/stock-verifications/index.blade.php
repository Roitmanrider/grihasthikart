@extends('layouts.admin')

@section('title', 'Stock Verification')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Stock Verification</h1>
        <div class="text-muted">Physical stock counts and resulting corrections.</div>
    </div>
    <a href="{{ route('admin.stock-verifications.create') }}" class="btn btn-success">Record Verification</a>
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
                    <th>System</th>
                    <th>Counted</th>
                    <th>Difference</th>
                    <th>Status</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stockAdjustments as $adjustment)
                    @php($difference = (float) $adjustment->after_quantity - (float) $adjustment->before_quantity)
                    <tr>
                        <td>{{ $adjustment->adjustment_date?->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $adjustment->productVariant?->product?->name }}</div>
                            <div class="small text-muted">{{ $adjustment->productVariant?->variant_name }} / {{ $adjustment->productVariant?->sku }}</div>
                        </td>
                        <td>{{ number_format((float) $adjustment->before_quantity, 3) }}</td>
                        <td>{{ number_format((float) $adjustment->after_quantity, 3) }}</td>
                        <td>{{ number_format($difference, 3) }}</td>
                        <td>
                            @if ((float) $adjustment->quantity > 0)
                                <span class="badge text-bg-warning">Adjusted</span>
                            @else
                                <span class="badge text-bg-success">Matched</span>
                            @endif
                        </td>
                        <td>{{ $adjustment->reference_number ?: 'None' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No stock verifications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $stockAdjustments->links() }}</div>
</div>
@endsection
