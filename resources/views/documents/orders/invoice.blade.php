<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoiceNumber }}</title>
    @include('documents.orders.partials.styles')
</head>
<body>
    <div class="document-actions">
        <button class="btn-print" type="button" onclick="window.print()">Print Invoice</button>
    </div>

    <main class="document-shell">
        <header class="doc-header">
            <div>
                <h1 class="doc-title">{{ $business['name'] ?? 'GrihasthiKart' }}</h1>
                <p class="doc-subtitle">Tax Invoice</p>
                @if (! empty($tax['company_legal_name']))
                    <div>{{ $tax['company_legal_name'] }}</div>
                @endif
                <div class="muted">{{ $tax['company_address'] ?? $business['address'] ?? '' }}</div>
                <div class="muted">
                    {{ collect([$business['city'] ?? null, $business['state'] ?? null, $business['pincode'] ?? null])->filter()->implode(', ') }}
                </div>
                @if (! empty($tax['company_gstin']))
                    <div class="muted">GSTIN: {{ $tax['company_gstin'] }}</div>
                @endif
                <div class="muted">{{ $business['support_email'] ?? '' }} {{ ! empty($business['support_phone']) ? ' / '.$business['support_phone'] : '' }}</div>
            </div>
            <div class="text-end">
                <div><strong>Invoice:</strong> {{ $invoiceNumber }}</div>
                <div><strong>Order:</strong> {{ $order->order_number }}</div>
                <div><strong>Order Date:</strong> {{ $order->placed_at?->format('d M Y, h:i A') }}</div>
                <div><strong>Payment:</strong> {{ strtoupper($order->payment_method) }} / {{ str($order->payment_status)->headline() }}</div>
                <div><strong>Status:</strong> {{ str($order->order_status)->headline() }}</div>
            </div>
        </header>

        <section class="doc-grid section">
            <div class="box">
                <strong>Bill To / Deliver To</strong>
                <div>{{ $order->customer_name }}</div>
                <div>{{ $order->customer_mobile }}</div>
                @if ($order->customer_email)
                    <div>{{ $order->customer_email }}</div>
                @endif
                <div class="muted">{{ $billingAddress }}</div>
            </div>
            <div class="box">
                <strong>Delivery</strong>
                <div>Date: {{ $order->delivery_date?->format('d M Y') ?: '-' }}</div>
                <div>Slot: {{ $order->delivery_slot ?: '-' }}</div>
                <div>Payment Note: {{ $paymentNote }}</div>
            </div>
        </section>

        <section class="section">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>HSN</th>
                        <th>Qty</th>
                        <th>MRP</th>
                        <th>Selling Price</th>
                        <th>Discount</th>
                        <th>Taxable Value</th>
                        <th>GST</th>
                        <th>GST Amount</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        @php
                            $taxableValue = max(0, (float) $item->line_subtotal - (float) $item->tax_amount);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $item->product_name_snapshot }}</strong>
                                <div class="muted">{{ $item->variant_name_snapshot }}</div>
                                <div class="muted">SKU: {{ $item->sku_snapshot }}</div>
                            </td>
                            <td>{{ $item->hsn_code_snapshot ?: '-' }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                            <td>Rs. {{ number_format((float) $item->mrp, 2) }}</td>
                            <td>Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>Rs. {{ number_format((float) $item->line_savings, 2) }}</td>
                            <td>Rs. {{ number_format($taxableValue, 2) }}</td>
                            <td>{{ number_format((float) ($item->gst_rate_snapshot ?? 0), 2) }}%</td>
                            <td>Rs. {{ number_format((float) $item->tax_amount, 2) }}</td>
                            <td>Rs. {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="doc-grid section">
            <div class="box">
                <strong>GST Summary</strong>
                <table style="margin-top: 8px;">
                    <thead>
                        <tr>
                            <th>Rate</th>
                            <th>Taxable</th>
                            <th>GST</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gstSummary as $summary)
                            <tr>
                                <td>{{ number_format($summary['rate'], 2) }}%</td>
                                <td>Rs. {{ number_format($summary['taxable_value'], 2) }}</td>
                                <td>Rs. {{ number_format($summary['gst_amount'], 2) }}</td>
                                <td>Rs. {{ number_format($summary['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="totals">
                <div class="summary-row"><span>Subtotal</span><strong>Rs. {{ number_format((float) $order->subtotal, 2) }}</strong></div>
                <div class="summary-row"><span>MRP Total</span><span>Rs. {{ number_format((float) $order->total_mrp, 2) }}</span></div>
                <div class="summary-row"><span>Savings</span><span>Rs. {{ number_format((float) $order->total_savings, 2) }}</span></div>
                @if ((float) $order->discount_total > 0)
                    <div class="summary-row"><span>Coupon Discount {{ $order->coupon_code_snapshot ? '('.$order->coupon_code_snapshot.')' : '' }}</span><span>- Rs. {{ number_format((float) $order->discount_total, 2) }}</span></div>
                @endif
                <div class="summary-row"><span>GST Total</span><span>Rs. {{ number_format((float) $order->tax_total, 2) }}</span></div>
                <div class="summary-row"><span>Delivery Charge</span><span>Rs. {{ number_format((float) $order->delivery_charge, 2) }}</span></div>
                <div class="summary-row grand-total"><span>Grand Total</span><span>Rs. {{ number_format((float) $order->grand_total, 2) }}</span></div>
            </div>
        </section>

        <section class="section muted">
            <div><strong>Terms:</strong> {{ $termsNote }}</div>
        </section>
    </main>
</body>
</html>
