@extends('layouts.admin')

@section('title', 'Preview Purchase Import')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Preview Purchase Import</h1>
        <div class="text-muted">Review CSV rows before posting stock inward.</div>
    </div>
    <a href="{{ route('admin.purchases.create') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($preview['errors'])
    <div class="alert alert-danger">
        <div class="fw-semibold mb-2">Fix these CSV issues and upload again.</div>
        <ul class="mb-0">
            @foreach ($preview['errors'] as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.purchases.import') }}">
    @csrf
    <input type="hidden" name="purchase_date" value="{{ $data['purchase_date'] }}">
    <input type="hidden" name="supplier_id" value="{{ $data['supplier_id'] ?? '' }}">
    <input type="hidden" name="bill_number" value="{{ $data['bill_number'] ?? '' }}">
    <input type="hidden" name="freight_allocation" value="{{ $data['freight_allocation'] ?? 0 }}">
    <input type="hidden" name="notes" value="{{ $data['notes'] ?? '' }}">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Import Summary</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2"><div class="text-muted small">Subtotal</div><div class="fw-semibold">Rs. {{ number_format($preview['totals']['subtotal'], 2) }}</div></div>
                <div class="col-md-2"><div class="text-muted small">Discount</div><div class="fw-semibold">Rs. {{ number_format($preview['totals']['discount_total'], 2) }}</div></div>
                <div class="col-md-2"><div class="text-muted small">CGST</div><div class="fw-semibold">Rs. {{ number_format($preview['totals']['cgst_total'], 2) }}</div></div>
                <div class="col-md-2"><div class="text-muted small">SGST</div><div class="fw-semibold">Rs. {{ number_format($preview['totals']['sgst_total'], 2) }}</div></div>
                <div class="col-md-2"><div class="text-muted small">GST</div><div class="fw-semibold">Rs. {{ number_format($preview['totals']['gst_total'], 2) }}</div></div>
                <div class="col-md-2"><div class="text-muted small">Grand Total</div><div class="fw-semibold">Rs. {{ number_format($preview['totals']['grand_total'], 2) }}</div></div>
                <div class="col-md-2"><div class="text-muted small">Freight Allocation</div><div class="fw-semibold">Rs. {{ number_format((float) ($data['freight_allocation'] ?? 0), 2) }}</div></div>
            </div>
            <div class="small text-muted mt-3">Freight allocation is stored for audit only and is not included in GST or grand total.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th>GST %</th>
                        <th>CGST</th>
                        <th>SGST</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($preview['items'] as $index => $item)
                        <tr>
                            <td>{{ $item['sku'] }}</td>
                            <td>{{ $item['product_name'] }} / {{ $item['variant_name'] }}</td>
                            <td>{{ number_format($item['quantity'], 3) }}</td>
                            <td>Rs. {{ number_format($item['purchase_price'], 2) }}</td>
                            <td>Rs. {{ number_format($item['discount_amount'], 2) }}</td>
                            <td>{{ number_format($item['gst_rate'], 2) }}%</td>
                            <td>{{ number_format($item['cgst_rate'], 2) }}% / Rs. {{ number_format($item['cgst_amount'], 2) }}</td>
                            <td>{{ number_format($item['sgst_rate'], 2) }}% / Rs. {{ number_format($item['sgst_amount'], 2) }}</td>
                            <td>Rs. {{ number_format($item['line_total'], 2) }}</td>
                        </tr>
                        @foreach (['product_variant_id', 'quantity', 'purchase_price', 'discount_amount', 'gst_rate', 'cgst_rate', 'sgst_rate', 'batch_number', 'expiry_date'] as $field)
                            <input type="hidden" name="items[{{ $index }}][{{ $field }}]" value="{{ $item[$field] ?? '' }}">
                        @endforeach
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">No CSV rows with quantity were found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-success" @disabled($preview['errors'] || ! $preview['items'])>Import Purchase</button>
        </div>
    </div>
</form>
@endsection
