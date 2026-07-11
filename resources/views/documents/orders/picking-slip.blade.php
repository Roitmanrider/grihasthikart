<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Picking Slip {{ $order->order_number }}</title>
    @include('documents.orders.partials.styles')
</head>
<body>
    <div class="document-actions">
        <button class="btn-print" type="button" onclick="window.print()">Print Picking Slip</button>
    </div>

    <main class="document-shell">
        <header class="doc-header">
            <div>
                <h1 class="doc-title">GrihasthiKart</h1>
                <p class="doc-subtitle">Picking Slip</p>
                <div class="muted">Internal store document</div>
            </div>
            <div class="text-end">
                <div><strong>Order:</strong> {{ $order->order_number }}</div>
                <div><strong>Delivery:</strong> {{ $order->delivery_date?->format('d M Y') ?: '-' }} / {{ $order->delivery_slot ?: '-' }}</div>
                <div><strong>Status:</strong> {{ str($order->order_status)->headline() }}</div>
            </div>
        </header>

        <section class="doc-grid section">
            <div class="box">
                <strong>Customer Area / Address</strong>
                <div>{{ $order->customer_name }}</div>
                <div>{{ $order->customer_mobile }}</div>
                <div class="muted">{{ $deliveryAddress }}</div>
            </div>
            <div class="box">
                <strong>Staff Notes</strong>
                <div class="muted">{{ $order->admin_notes ?: $order->notes ?: 'No notes.' }}</div>
            </div>
        </section>

        <section class="section">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product / Variant</th>
                        <th>Qty</th>
                        <th>Rack / Location</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->sku_snapshot }}</td>
                            <td>
                                <strong>{{ $item->product_name_snapshot }}</strong>
                                <div class="muted">{{ $item->variant_name_snapshot }}</div>
                            </td>
                            <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                            <td>
                                {{ $item->productVariant?->inventories?->pluck('stockLocation.name')->filter()->implode(', ') ?: '-' }}
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
