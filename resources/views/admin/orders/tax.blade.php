@extends('layouts.admin')

@section('title', 'Order Tax Detail')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Tax Detail {{ $order->order_number }}</h1><div class="text-muted">{{ $order->customer_name }} / {{ strtoupper($order->payment_method) }}</div></div><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">Back</a></div>
<div class="alert alert-light border">GST report uses item-level tax snapshots. Order-level coupon discounts are shown separately for reconciliation.</div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Item</th><th>GST Rate</th><th>Taxable</th><th>GST</th><th>Total</th></tr></thead><tbody>@foreach($items as $row)<tr><td>{{ $row['item']->product_name_snapshot }}<div class="small text-muted">{{ $row['item']->sku_snapshot }}</div></td><td>{{ number_format($row['gst_rate'],2) }}%</td><td>Rs. {{ number_format($row['taxable_amount'],2) }}</td><td>Rs. {{ number_format($row['gst_amount'],2) }}</td><td>Rs. {{ number_format($row['gross_amount'],2) }}</td></tr>@endforeach</tbody></table></div></div>
<div class="card border-0 shadow-sm mt-4"><div class="card-body"><div>Order discount: Rs. {{ number_format((float)$order->discount_total,2) }}</div><div>Delivery charge: Rs. {{ number_format((float)$order->delivery_charge,2) }}</div><div class="fw-semibold">Grand total: Rs. {{ number_format((float)$order->grand_total,2) }}</div></div></div>
@endsection
