@extends('layouts.admin')

@section('title', 'GST Summary')

@section('admin-content')
<h1 class="h3 mb-4">GST Summary Report</h1>
@include('admin.reports.partials.filters')
<div class="alert alert-light border">GST report uses item-level tax snapshots. Order-level coupon discounts are shown separately for reconciliation.</div>
<div class="row g-4 mb-4">
    @foreach ([
        'Orders' => $summary['total_orders'],
        'Gross Orders' => 'Rs. '.number_format($summary['gross_order_amount'], 2),
        'Taxable' => 'Rs. '.number_format($summary['taxable_amount'], 2),
        'GST Collected' => 'Rs. '.number_format($summary['total_gst_collected'], 2),
        'Coupon Discount' => 'Rs. '.number_format($summary['total_coupon_discount'], 2),
        'Grand Total' => 'Rs. '.number_format($summary['grand_total'], 2),
    ] as $label => $value)
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">{{ $label }}</div><div class="h5">{{ $value }}</div></div></div></div>
    @endforeach
</div>
<div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Payment Split</div><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Method</th><th>Orders</th><th>Amount</th></tr></thead><tbody>@forelse($summary['payment_split'] as $method => $split)<tr><td>{{ strtoupper($method) }}</td><td>{{ $split['count'] }}</td><td>Rs. {{ number_format($split['amount'],2) }}</td></tr>@empty<tr><td colspan="3" class="text-muted text-center py-4">No orders.</td></tr>@endforelse</tbody></table></div></div>
@endsection
