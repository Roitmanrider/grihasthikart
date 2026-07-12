@extends('layouts.admin')

@section('title', 'New Purchase')

@php
    $oldItems = old('items', array_fill(0, 8, []));
@endphp

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">New Purchase</h1>
        <div class="text-muted">Record stock inward against purchased product variants.</div>
    </div>
    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">CSV Import</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.purchases.preview') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Purchase Date</label>
                <input type="date" name="purchase_date" value="{{ now()->toDateString() }}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">Not recorded</option>
                    @foreach ($options['suppliers'] as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Bill Number</label>
                <input type="text" name="bill_number" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Freight Allocation</label>
                <input type="number" step="0.01" min="0" name="freight_allocation" class="form-control" placeholder="Audit only">
            </div>
            <div class="col-md-2">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
            </div>
            <div class="col-12 d-flex gap-2">
                <a href="{{ route('admin.purchases.template') }}" class="btn btn-outline-success">Download Template</a>
                <button class="btn btn-outline-secondary">Preview CSV</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.purchases.store') }}" data-purchase-form>
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Purchase Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="purchase_date">Purchase Date</label>
                    <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="bill_number">Bill Number</label>
                    <input id="bill_number" type="text" name="bill_number" value="{{ old('bill_number') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="">Not recorded</option>
                        @foreach ($options['suppliers'] as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="freight_allocation">Freight Allocation</label>
                    <input id="freight_allocation" type="number" step="0.01" min="0" name="freight_allocation" value="{{ old('freight_allocation', 0) }}" class="form-control" placeholder="Audit only">
                    <div class="form-text">Transportation/labor audit only. Not included in grand total or GST.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3">
            <span class="fw-semibold">Items</span>
            <button type="button" class="btn btn-sm btn-outline-success" data-add-row>Add Item Row</button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 320px;">Variant</th>
                        <th>Qty</th>
                        <th>Purchase Price</th>
                        <th>Discount</th>
                        <th>GST %</th>
                        <th>CGST %</th>
                        <th>SGST %</th>
                        <th>CGST Amt</th>
                        <th>SGST Amt</th>
                        <th>Total GST</th>
                        <th>Line Total</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody data-items-body>
                    @foreach ($oldItems as $index => $oldItem)
                        <tr data-item-row>
                            <td>
                                <select name="items[{{ $index }}][product_variant_id]" class="form-select" data-field="variant">
                                    <option value="">Select variant</option>
                                    @foreach ($options['variants'] as $variant)
                                        @php
                                            $currentStock = $variant->inventories->sum(fn ($inventory) => $inventory->available_quantity);
                                        @endphp
                                        <option value="{{ $variant->id }}" @selected((string) old("items.$index.product_variant_id") === (string) $variant->id)>
                                            {{ $variant->product?->name }} / {{ $variant->variant_name }} / {{ $variant->sku }} / Stock: {{ number_format($currentStock, 3) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.001" min="0" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity") }}" class="form-control" data-field="quantity"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][purchase_price]" value="{{ old("items.$index.purchase_price") }}" class="form-control" data-field="purchase_price"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][discount_amount]" value="{{ old("items.$index.discount_amount", 0) }}" class="form-control" data-field="discount_amount"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][gst_rate]" value="{{ old("items.$index.gst_rate", 0) }}" class="form-control" data-field="gst_rate"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][cgst_rate]" value="{{ old("items.$index.cgst_rate", 0) }}" class="form-control" data-field="cgst_rate"></td>
                            <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][sgst_rate]" value="{{ old("items.$index.sgst_rate", 0) }}" class="form-control" data-field="sgst_rate"></td>
                            <td><input type="text" class="form-control" data-field="cgst_amount" readonly></td>
                            <td><input type="text" class="form-control" data-field="sgst_amount" readonly></td>
                            <td><input type="text" class="form-control" data-field="gst_amount" readonly></td>
                            <td><input type="text" class="form-control" data-field="line_total" readonly></td>
                            <td><input type="text" name="items[{{ $index }}][batch_number]" value="{{ old("items.$index.batch_number") }}" class="form-control"></td>
                            <td><input type="date" name="items[{{ $index }}][expiry_date]" value="{{ old("items.$index.expiry_date") }}" class="form-control"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            <div class="row g-3 align-items-end">
                <div class="col-md">
                    <div class="small text-muted">Subtotal: <strong data-total="subtotal">Rs. 0.00</strong></div>
                    <div class="small text-muted">Discount: <strong data-total="discount_total">Rs. 0.00</strong></div>
                    <div class="small text-muted">CGST: <strong data-total="cgst_total">Rs. 0.00</strong></div>
                    <div class="small text-muted">SGST: <strong data-total="sgst_total">Rs. 0.00</strong></div>
                    <div class="small text-muted">GST: <strong data-total="gst_total">Rs. 0.00</strong></div>
                    <div class="small text-muted">Grand Total: <strong data-total="grand_total">Rs. 0.00</strong></div>
                </div>
                <div class="col-md-auto d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-success">Post Purchase</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchaseConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5">Confirm Posted Purchase</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border">Posted purchases cannot be edited or deleted. Freight allocation is audit-only and is not included in grand total.</div>
                    <dl class="row mb-0">
                        <dt class="col-6">Subtotal</dt><dd class="col-6 text-end" data-modal-total="subtotal">Rs. 0.00</dd>
                        <dt class="col-6">Discount</dt><dd class="col-6 text-end" data-modal-total="discount_total">Rs. 0.00</dd>
                        <dt class="col-6">CGST</dt><dd class="col-6 text-end" data-modal-total="cgst_total">Rs. 0.00</dd>
                        <dt class="col-6">SGST</dt><dd class="col-6 text-end" data-modal-total="sgst_total">Rs. 0.00</dd>
                        <dt class="col-6">Total GST</dt><dd class="col-6 text-end" data-modal-total="gst_total">Rs. 0.00</dd>
                        <dt class="col-6">Freight Allocation</dt><dd class="col-6 text-end" data-modal-total="freight_allocation">Rs. 0.00</dd>
                        <dt class="col-6">Grand Total</dt><dd class="col-6 text-end fw-semibold" data-modal-total="grand_total">Rs. 0.00</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Review</button>
                    <button type="button" class="btn btn-success" data-confirm-submit>Confirm & Post</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-purchase-form]');
    if (!form) return;

    const body = form.querySelector('[data-items-body]');
    const addButton = form.querySelector('[data-add-row]');
    const maxRows = 100;
    const money = (value) => 'Rs. ' + Number(value || 0).toFixed(2);
    let confirmed = false;

    function number(row, field) {
        return parseFloat(row.querySelector('[data-field="' + field + '"]')?.value || '0') || 0;
    }

    function setField(row, field, value) {
        const input = row.querySelector('[data-field="' + field + '"]');
        if (input) input.value = Number(value || 0).toFixed(2);
    }

    function recalculateRow(row, changedField) {
        const gst = row.querySelector('[data-field="gst_rate"]');
        const cgst = row.querySelector('[data-field="cgst_rate"]');
        const sgst = row.querySelector('[data-field="sgst_rate"]');

        if (changedField === 'gst_rate') {
            const half = number(row, 'gst_rate') / 2;
            cgst.value = half.toFixed(2);
            sgst.value = half.toFixed(2);
        } else if (changedField === 'cgst_rate') {
            sgst.value = number(row, 'cgst_rate').toFixed(2);
            gst.value = (number(row, 'cgst_rate') + number(row, 'sgst_rate')).toFixed(2);
        } else if (changedField === 'sgst_rate') {
            cgst.value = number(row, 'sgst_rate').toFixed(2);
            gst.value = (number(row, 'cgst_rate') + number(row, 'sgst_rate')).toFixed(2);
        }

        const base = number(row, 'quantity') * number(row, 'purchase_price');
        const discount = Math.min(number(row, 'discount_amount'), base);
        const taxable = Math.max(0, base - discount);
        const cgstAmount = taxable * number(row, 'cgst_rate') / 100;
        const sgstAmount = taxable * number(row, 'sgst_rate') / 100;
        const gstAmount = cgstAmount + sgstAmount;

        setField(row, 'cgst_amount', cgstAmount);
        setField(row, 'sgst_amount', sgstAmount);
        setField(row, 'gst_amount', gstAmount);
        setField(row, 'line_total', taxable + gstAmount);
    }

    function totals() {
        const total = {subtotal: 0, discount_total: 0, cgst_total: 0, sgst_total: 0, gst_total: 0, grand_total: 0};
        body.querySelectorAll('[data-item-row]').forEach((row) => {
            const variant = row.querySelector('[data-field="variant"]')?.value;
            if (!variant) return;
            const base = number(row, 'quantity') * number(row, 'purchase_price');
            total.subtotal += base;
            total.discount_total += Math.min(number(row, 'discount_amount'), base);
            total.cgst_total += number(row, 'cgst_amount');
            total.sgst_total += number(row, 'sgst_amount');
            total.gst_total += number(row, 'gst_amount');
            total.grand_total += number(row, 'line_total');
        });
        total.freight_allocation = parseFloat(form.querySelector('[name="freight_allocation"]')?.value || '0') || 0;
        return total;
    }

    function refresh(changedRow, changedField) {
        if (changedRow) recalculateRow(changedRow, changedField);
        const total = totals();
        Object.entries(total).forEach(([key, value]) => {
            document.querySelectorAll('[data-total="' + key + '"], [data-modal-total="' + key + '"]').forEach((node) => node.textContent = money(value));
        });
    }

    function reindexRows() {
        body.querySelectorAll('[data-item-row]').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });
    }

    addButton.addEventListener('click', function () {
        const rows = body.querySelectorAll('[data-item-row]');
        if (rows.length >= maxRows) return;
        const clone = rows[0].cloneNode(true);
        clone.querySelectorAll('input').forEach((input) => input.value = input.type === 'number' && input.dataset.field !== 'quantity' && input.dataset.field !== 'purchase_price' ? '0' : '');
        clone.querySelectorAll('select').forEach((select) => select.value = '');
        body.appendChild(clone);
        reindexRows();
        refresh();
    });

    body.addEventListener('input', function (event) {
        const row = event.target.closest('[data-item-row]');
        if (!row) return;
        refresh(row, event.target.dataset.field);
    });

    form.querySelector('[name="freight_allocation"]').addEventListener('input', () => refresh());

    form.addEventListener('submit', function (event) {
        if (confirmed) return;
        event.preventDefault();
        refresh();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('purchaseConfirmModal')).show();
    });

    form.querySelector('[data-confirm-submit]').addEventListener('click', function () {
        confirmed = true;
        form.requestSubmit();
    });

    body.querySelectorAll('[data-item-row]').forEach((row) => recalculateRow(row));
    refresh();
});
</script>
@endpush
