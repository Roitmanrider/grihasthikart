@extends('layouts.frontend')
@section('title','My Orders')
@section('content')
<section class="py-5"><div class="container"><h1 class="h3 mb-4">My Orders</h1><div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Order</th><th>Status</th><th>Total</th><th>Placed</th><th></th></tr></thead><tbody>@forelse($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->order_status }}</td><td>Rs. {{ number_format((float)$order->grand_total,2) }}</td><td>{{ $order->placed_at?->format('d M Y') }}</td><td><a href="{{ route('customer.orders.show',$order->order_number) }}" class="btn btn-sm btn-outline-success">View</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No orders.</td></tr>@endforelse</tbody></table></div><div class="card-footer bg-white">{{ $orders->links() }}</div></div></div></section>
@endsection
