@extends('layouts.frontend')
@section('title','My Account')
@section('content')
<section class="py-5"><div class="container">
<div class="d-flex justify-content-between mb-4"><div><h1 class="h3 mb-1">My Account</h1><div class="text-muted">{{ $customer->mobile }}</div></div><form method="POST" action="{{ route('customer.logout') }}">@csrf<button class="btn btn-outline-secondary">Logout</button></form></div>
<div class="row g-4">
<div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="fw-semibold">{{ $customer->name }}</div>@if($customer->is_premium)<span class="badge text-bg-success mt-2">Premium</span>@endif<div class="text-muted mt-2">Cashback coming soon</div></div></div></div>
<div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="h4">{{ $customer->addresses_count }}</div><a href="{{ route('customer.addresses.index') }}">Addresses</a></div></div></div>
<div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><a href="{{ route('cart.show') }}" class="btn btn-success w-100">View Cart</a></div></div></div>
</div>
<div class="card border-0 shadow-sm mt-4"><div class="card-header bg-white fw-semibold">Latest Orders</div><div class="card-body">@forelse($orders as $order)<div><a href="{{ route('customer.orders.show', $order->order_number) }}">{{ $order->order_number }}</a> - {{ $order->order_status }} - Rs. {{ number_format((float)$order->grand_total,2) }}</div>@empty<div class="text-muted">No orders yet.</div>@endforelse</div></div>
</div></section>
@endsection
