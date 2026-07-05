@extends('layouts.frontend')
@section('title','Order '.$order->order_number)
@section('content')
<section class="py-5"><div class="container"><h1 class="h3 mb-4">{{ $order->order_number }}</h1><div class="card border-0 shadow-sm"><div class="card-body"><div class="mb-3">Status: <strong>{{ $order->order_status }}</strong> / COD {{ $order->payment_status }}</div>@foreach($order->items as $item)<div class="border-bottom pb-2 mb-2"><div class="fw-semibold">{{ $item->product_name_snapshot }}</div><div class="small text-muted">{{ $item->variant_name_snapshot }} x {{ $item->quantity }} - Rs. {{ number_format((float)$item->line_total,2) }}</div></div>@endforeach<hr><div class="h5">Grand Total: Rs. {{ number_format((float)$order->grand_total,2) }}</div></div></div></div></section>
@endsection
