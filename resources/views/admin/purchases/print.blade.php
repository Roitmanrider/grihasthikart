<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $purchase->purchase_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
            <div>
                <h1 class="h4 mb-1">GrihasthiKart Purchase Entry</h1>
                <div class="text-muted">{{ $purchase->purchase_number }}</div>
            </div>
            <div class="text-end">
                <div><strong>Date:</strong> {{ $purchase->purchase_date?->format('d M Y') }}</div>
                <div><strong>Bill:</strong> {{ $purchase->bill_number ?: 'N/A' }}</div>
                <div><strong>Status:</strong> {{ str($purchase->status)->headline() }}</div>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>GST</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->items as $item)
                    <tr>
                        <td>{{ $item->productVariant?->product?->name }} / {{ $item->productVariant?->variant_name }}</td>
                        <td>{{ $item->sku }}</td>
                        <td>{{ number_format((float) $item->quantity, 3) }}</td>
                        <td>Rs. {{ number_format((float) $item->purchase_price, 2) }}</td>
                        <td>{{ number_format((float) $item->gst_rate, 2) }}%</td>
                        <td>Rs. {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row justify-content-end">
            <div class="col-md-4">
                <div class="d-flex justify-content-between"><span>Subtotal</span><strong>Rs. {{ number_format((float) $purchase->subtotal, 2) }}</strong></div>
                <div class="d-flex justify-content-between"><span>GST</span><span>Rs. {{ number_format((float) $purchase->gst_total, 2) }}</span></div>
                <div class="d-flex justify-content-between"><span>Discount</span><span>Rs. {{ number_format((float) $purchase->discount_total, 2) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between h5"><span>Total</span><strong>Rs. {{ number_format((float) $purchase->grand_total, 2) }}</strong></div>
            </div>
        </div>

        @if ($purchase->notes)
            <div class="mt-4">
                <strong>Notes</strong>
                <div class="text-muted">{{ $purchase->notes }}</div>
            </div>
        @endif
    </main>
</body>
</html>
