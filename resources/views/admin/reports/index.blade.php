@extends('layouts.admin')

@section('title', 'Reports Dashboard')

@php
    $money = fn ($amount) => 'Rs. '.number_format((float) $amount, 2);
    $paymentLabels = [
        'cod' => 'COD',
        'qr' => 'QR',
        'razorpay' => 'Razorpay',
    ];
@endphp

@section('admin-content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Reports Dashboard</h1>
        <div class="text-muted">Operational summaries for sales, inventory, purchases, taxes, and returns.</div>
    </div>
</div>

<section class="mb-4">
    <h2 class="h5 mb-3">Sales Summary</h2>
    <div class="row g-3">
        @foreach ([
            'Today Sales' => $money($dashboard['sales']['today_sales']),
            'This Month Sales' => $money($dashboard['sales']['month_sales']),
            'Total Orders' => $dashboard['sales']['total_orders'],
            'Delivered Orders' => $dashboard['sales']['delivered_orders'],
            'Cancelled Orders' => $dashboard['sales']['cancelled_orders'],
            'Return / Refund Amount' => $money($dashboard['sales']['return_refund_amount']),
        ] as $label => $value)
            <div class="col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="h5 mb-0">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white fw-semibold">Payment Method Breakdown</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Method</th>
                        <th>Orders</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dashboard['sales']['payment_methods'] as $method => $split)
                        <tr>
                            <td>{{ $paymentLabels[$method] ?? ucfirst((string) $method) }}</td>
                            <td>{{ $split['count'] }}</td>
                            <td>{{ $money($split['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    <section class="col-lg-6">
        <h2 class="h5 mb-3">Inventory Summary</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    @foreach ([
                        'Total Products' => $dashboard['inventory']['total_products'],
                        'Total Variants' => $dashboard['inventory']['total_variants'],
                        'Low Stock Count' => $dashboard['inventory']['low_stock_count'],
                        'Out of Stock Count' => $dashboard['inventory']['out_of_stock_count'],
                        'Stock Value' => $money($dashboard['inventory']['stock_value']),
                    ] as $label => $value)
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="fw-semibold">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="col-lg-6">
        <h2 class="h5 mb-3">Purchase Summary</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @unless ($dashboard['purchase']['available'])
                    <div class="alert alert-light border mb-3">Purchase tables are not available in this environment.</div>
                @endunless
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted small">Today Purchases</div>
                        <div class="fw-semibold">{{ $money($dashboard['purchase']['today_purchases']) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">This Month Purchases</div>
                        <div class="fw-semibold">{{ $money($dashboard['purchase']['month_purchases']) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Input GST</div>
                        <div class="fw-semibold">{{ $money($dashboard['purchase']['input_gst']) }}</div>
                    </div>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Supplier</th><th>Purchases</th><th>Total</th></tr></thead>
                        <tbody>
                            @forelse ($dashboard['purchase']['supplier_totals'] as $supplier)
                                <tr><td>{{ $supplier['supplier'] }}</td><td>{{ $supplier['count'] }}</td><td>{{ $money($supplier['amount']) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No supplier purchases.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="row g-4 mb-4">
    <section class="col-lg-6">
        <h2 class="h5 mb-3">Tax Summary</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @if (! $dashboard['tax']['has_exact_output'] || ! $dashboard['tax']['has_exact_input'])
                    <div class="alert alert-light border">Some exact GST fields are unavailable, so unavailable values are shown as zero.</div>
                @endif
                <div class="row g-3">
                    <div class="col-sm-4"><div class="text-muted small">Output GST</div><div class="fw-semibold">{{ $money($dashboard['tax']['output_gst']) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Input GST</div><div class="fw-semibold">{{ $money($dashboard['tax']['input_gst']) }}</div></div>
                    <div class="col-sm-4"><div class="text-muted small">Net GST Payable</div><div class="fw-semibold">{{ $money($dashboard['tax']['net_gst_payable']) }}</div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="col-lg-6">
        <h2 class="h5 mb-3">Returns Summary</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @unless ($dashboard['returns']['available'])
                    <div class="alert alert-light border mb-3">Return tables are not available in this environment.</div>
                @endunless
                <div class="row g-3">
                    @foreach ([
                        'Requested Returns' => $dashboard['returns']['requested'],
                        'Approved Returns' => $dashboard['returns']['approved'],
                        'Rejected Returns' => $dashboard['returns']['rejected'],
                        'Refunded Returns' => $dashboard['returns']['refunded'],
                        'Refund Amount' => $money($dashboard['returns']['refund_amount']),
                    ] as $label => $value)
                        <div class="col-sm-6">
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="fw-semibold">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>

<section>
    <h2 class="h5 mb-3">Quick Links</h2>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.orders.index') }}">Orders</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.purchases.index') }}">Purchases</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.stock-adjustments.index') }}">Stock Adjustments</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.returns.index') }}">Returns</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.reports.gst-summary') }}">Tax Reports</a>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.product-imports.index') }}">Product Import</a>
    </div>
</section>
@endsection
