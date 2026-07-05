@extends('layouts.admin')

@section('title', 'Monthly GST')

@section('admin-content')
<h1 class="h3 mb-4">Monthly GST Report</h1>
@include('admin.reports.partials.filters')
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Month</th><th>Orders</th><th>Taxable</th><th>GST</th><th>Coupon Discounts</th><th>Delivery</th><th>Grand Total</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row['month'] }}</td><td>{{ $row['order_count'] }}</td><td>Rs. {{ number_format($row['taxable_amount'],2) }}</td><td>Rs. {{ number_format($row['total_gst_collected'],2) }}</td><td>Rs. {{ number_format($row['coupon_discounts'],2) }}</td><td>Rs. {{ number_format($row['delivery_charges'],2) }}</td><td>Rs. {{ number_format($row['grand_total'],2) }}</td></tr>@empty<tr><td colspan="7" class="text-muted text-center py-4">No rows.</td></tr>@endforelse</tbody></table></div></div>
@endsection
