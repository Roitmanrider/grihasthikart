<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing Slip {{ $order->order_number }}</title>
    @include('documents.orders.partials.styles')
</head>
<body>
    <div class="document-actions">
        <button class="btn-print" type="button" onclick="window.print()">Print Packing Slip</button>
    </div>

    <main class="document-shell">
        <header class="doc-header">
            <div>
                <h1 class="doc-title">GrihasthiKart</h1>
                <p class="doc-subtitle">Packing Slip</p>
                <div class="muted">Customer package document</div>
            </div>
            <div class="text-end">
                <div><strong>Order:</strong> {{ $order->order_number }}</div>
                <div><strong>Delivery:</strong> {{ $order->delivery_date?->format('d M Y') ?: '-' }} / {{ $order->delivery_slot ?: '-' }}</div>
            </div>
        </header>

        <section class="doc-grid section">
            <div class="box">
                <strong>Deliver To</strong>
                <div>{{ $order->customer_name }}</div>
                <div>{{ $order->customer_mobile }}</div>
                <div class="muted">{{ $deliveryAddress }}</div>
            </div>
            <div class="box">
                <strong>Package Check</strong>
                <div><span class="badge">Packed</span> <span class="badge">Verified</span> <span class="badge">Out for delivery</span></div>
            </div>
        </section>

        <section class="section">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}</td>
                            <td>{{ $item->variant_name_snapshot }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="section muted">
            Please check all items at delivery. For support, contact GrihasthiKart customer care.
        </section>
    </main>
</body>
</html>
