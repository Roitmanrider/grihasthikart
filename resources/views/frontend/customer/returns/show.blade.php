@extends('layouts.frontend')

@section('title', 'Return '.$returnRequest->return_number)

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ $returnRequest->return_number }}</h1>
                <div class="text-muted">Order {{ $returnRequest->order?->order_number }}</div>
            </div>
            <a href="{{ route('customer.returns.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Return Items</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Refund</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($returnRequest->items as $item)
                                    <tr>
                                        <td>{{ $item->orderItem?->product_name_snapshot }} / {{ $item->orderItem?->variant_name_snapshot }}</td>
                                        <td>{{ number_format((float) $item->quantity, 3) }}</td>
                                        <td>Rs. {{ number_format((float) $item->refund_amount, 2) }}</td>
                                        <td>{{ $item->reason ?: 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Summary</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between"><span>Status</span><span class="badge text-bg-light border">{{ str($returnRequest->status)->headline() }}</span></div>
                        <div class="d-flex justify-content-between mt-2"><span>Refund Amount</span><strong>Rs. {{ number_format((float) $returnRequest->refund_amount, 2) }}</strong></div>
                        @if ($returnRequest->customer_notes)
                            <div class="text-muted small mt-3">{{ $returnRequest->customer_notes }}</div>
                        @endif
                        @if ($returnRequest->admin_notes)
                            <hr>
                            <div class="fw-semibold">Admin Note</div>
                            <div class="text-muted small">{{ $returnRequest->admin_notes }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
