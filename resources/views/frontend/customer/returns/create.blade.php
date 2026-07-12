@extends('layouts.frontend')

@section('title', 'Request Return')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Request Return</h1>
                <div class="text-muted">{{ $order->order_number }}</div>
            </div>
            <a href="{{ route('customer.orders.show', $order->order_number) }}" class="btn btn-outline-secondary">Back</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('customer.returns.store') }}" class="card border-0 shadow-sm">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" for="reason">Overall Reason</label>
                        <input id="reason" type="text" name="reason" value="{{ old('reason') }}" class="form-control">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="customer_notes">Notes</label>
                        <input id="customer_notes" type="text" name="customer_notes" value="{{ old('customer_notes') }}" class="form-control">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Ordered</th>
                                <th>Returnable</th>
                                <th>Return Qty</th>
                                <th>Reason</th>
                                <th>Condition</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                                        <div class="fw-semibold">{{ $item->product_name_snapshot }}</div>
                                        <div class="small text-muted">{{ $item->variant_name_snapshot }} / {{ $item->sku_snapshot }}</div>
                                        <div class="small text-muted">Paid price: Rs. {{ number_format((float) $item->unit_price, 2) }}</div>
                                    </td>
                                    <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) ($remaining[$item->id] ?? 0), 3), '0'), '.') }}</td>
                                    <td><input type="number" step="0.001" min="0" max="{{ $remaining[$item->id] ?? 0 }}" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity", 0) }}" class="form-control"></td>
                                    <td><input type="text" name="items[{{ $index }}][reason]" value="{{ old("items.$index.reason") }}" class="form-control"></td>
                                    <td><input type="text" name="items[{{ $index }}][condition]" value="{{ old("items.$index.condition") }}" class="form-control"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('customer.orders.show', $order->order_number) }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-success">Submit Return Request</button>
            </div>
        </form>
    </div>
</section>
@endsection
