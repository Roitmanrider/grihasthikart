@extends('layouts.admin')

@section('title', 'GST by Rate')

@section('admin-content')
<h1 class="h3 mb-4">GST by Rate Report</h1>
@include('admin.reports.partials.filters')
<div class="alert alert-light border">GST report uses item-level tax snapshots. Order-level coupon discounts are shown separately for reconciliation.</div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>GST Rate</th><th>Taxable</th><th>Output CGST</th><th>Output SGST</th><th>GST</th><th>Gross</th><th>Items</th><th>Quantity</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ number_format($row['gst_rate'],2) }}%</td><td>Rs. {{ number_format($row['taxable_amount'],2) }}</td><td>Rs. {{ number_format($row['output_cgst'],2) }}</td><td>Rs. {{ number_format($row['output_sgst'],2) }}</td><td>Rs. {{ number_format($row['total_gst_collected'],2) }}</td><td>Rs. {{ number_format($row['gross_amount'],2) }}</td><td>{{ $row['order_item_count'] }}</td><td>{{ number_format($row['quantity_total'],3) }}</td></tr>@empty<tr><td colspan="8" class="text-muted text-center py-4">No rows.</td></tr>@endforelse</tbody></table></div></div>
@endsection
