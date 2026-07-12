@extends('layouts.frontend')

@section('title', 'My Returns')

@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="h3 mb-4">My Returns</h1>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Return</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $return)
                            <tr>
                                <td>{{ $return->return_number }}</td>
                                <td>{{ $return->order?->order_number }}</td>
                                <td><span class="badge text-bg-light border">{{ str($return->status)->headline() }}</span></td>
                                <td>Rs. {{ number_format((float) $return->refund_amount, 2) }}</td>
                                <td>{{ $return->requested_at?->format('d M Y') }}</td>
                                <td><a href="{{ route('customer.returns.show', $return) }}" class="btn btn-sm btn-outline-success">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No return requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $returns->links() }}</div>
        </div>
    </div>
</section>
@endsection
