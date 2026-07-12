@extends('layouts.admin')

@section('title', 'Returns')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Returns</h1>
        <div class="text-muted">Customer return and refund requests.</div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Return</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td>{{ $return->return_number }}</td>
                        <td>{{ $return->order?->order_number }}</td>
                        <td>{{ $return->customer?->name }}</td>
                        <td><span class="badge text-bg-light border">{{ str($return->status)->headline() }}</span></td>
                        <td>Rs. {{ number_format((float) $return->refund_amount, 2) }}</td>
                        <td>{{ $return->requested_at?->format('d M Y') }}</td>
                        <td class="text-end"><a href="{{ route('admin.returns.show', $return) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No return requests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $returns->links() }}</div>
</div>
@endsection
