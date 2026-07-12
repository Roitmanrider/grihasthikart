@extends('layouts.admin')

@section('title', 'Monthly GST')

@section('admin-content')
<h1 class="h3 mb-4">Monthly GST Report</h1>
@include('admin.reports.partials.filters')
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Month</th><th>Orders</th><th>Taxable</th><th>Output CGST</th><th>Output SGST</th><th>Input CGST</th><th>Input SGST</th><th>Net CGST</th><th>Net SGST</th><th>Total Net GST</th><th>Grand Total</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row['month'] }}</td><td>{{ $row['order_count'] }}</td><td>Rs. {{ number_format($row['taxable_amount'],2) }}</td><td>Rs. {{ number_format($row['output_cgst'],2) }}</td><td>Rs. {{ number_format($row['output_sgst'],2) }}</td><td>Rs. {{ number_format($row['input_cgst'],2) }}</td><td>Rs. {{ number_format($row['input_sgst'],2) }}</td><td>Rs. {{ number_format($row['net_cgst_payable'],2) }}</td><td>Rs. {{ number_format($row['net_sgst_payable'],2) }}</td><td>Rs. {{ number_format($row['total_net_gst_payable'],2) }}</td><td>Rs. {{ number_format($row['grand_total'],2) }}</td></tr>@empty<tr><td colspan="11" class="text-muted text-center py-4">No rows.</td></tr>@endforelse</tbody></table></div></div>
@endsection
