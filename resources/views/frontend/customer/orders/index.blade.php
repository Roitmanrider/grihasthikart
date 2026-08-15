@extends('layouts.frontend')
@section('title','My Orders')
@section('content')
<section class="py-5">
    <div class="container">
        <h1 class="h3 mb-4">My Orders</h1>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Status</th>
                            <th>Merchandise Amount</th>
                            <th>Delivery Charge</th>
                            <th>Customer Credit</th>
                            <th>Final Amount</th>
                            <th>Placed</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $order->order_number }}</div>
                                    @if($order->returnRequests->isNotEmpty())
                                        <span class="badge text-bg-light border">{{ str($order->returnRequests->sortByDesc('created_at')->first()->status)->headline() }}</span>
                                    @endif
                                </td>
                                <td>{{ str($order->order_status)->headline() }}</td>
                                <td>Rs. {{ number_format((float)$order->subtotal,2) }}</td>
                                <td>{{ (float)$order->delivery_charge > 0 ? 'Rs. '.number_format((float)$order->delivery_charge,2) : 'Free' }}</td>
                                <td>{{ (float)$order->customer_credit_used > 0 ? '- Rs. '.number_format((float)$order->customer_credit_used,2) : '-' }}</td>
                                <td class="fw-semibold">Rs. {{ number_format((float)$order->grand_total,2) }}</td>
                                <td>{{ $order->placed_at?->format('d M Y') }}</td>
                                <td><a href="{{ route('customer.orders.show',$order->order_number) }}" class="btn btn-sm btn-outline-success">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $orders->links() }}</div>
        </div>
    </div>
</section>
@endsection
