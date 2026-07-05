@extends('layouts.admin')

@section('title', 'Payment Details')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $payment->payment_number }}</h1>
        <div class="text-muted">Order {{ $payment->order?->order_number }}</div>
    </div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Payment Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">Method</div><div class="fw-semibold">{{ strtoupper($payment->payment_method) }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Status</div><div class="fw-semibold">{{ str_replace('_', ' ', $payment->payment_status) }}</div></div>
                    <div class="col-md-4"><div class="text-muted small">Amount</div><div class="fw-semibold">Rs. {{ number_format((float) $payment->amount, 2) }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Gateway Order</div><div>{{ $payment->gateway_order_id ?: '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Gateway Payment</div><div>{{ $payment->gateway_payment_id ?: '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">QR Reference</div><div>{{ $payment->qr_reference ?: '-' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Verified At</div><div>{{ $payment->verified_at?->format('d M Y, h:i A') ?: '-' }}</div></div>
                </div>

                @if ($payment->proof_path)
                    <div class="mt-4">
                        <a href="{{ Storage::url($payment->proof_path) }}" target="_blank" class="btn btn-outline-success">Open Payment Proof</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Transaction Log</div>
            <div class="card-body">
                @forelse ($payment->transactions as $transaction)
                    <div class="border-bottom pb-2 mb-2">
                        <div class="fw-semibold">{{ str_replace('_', ' ', $transaction->transaction_type) }} / {{ $transaction->status }}</div>
                        <div class="small text-muted">{{ $transaction->note ?: 'No note' }} / {{ $transaction->created_at?->format('d M Y, h:i A') }}</div>
                    </div>
                @empty
                    <div class="text-muted">No transactions found.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Order</div>
            <div class="card-body">
                <div class="fw-semibold">{{ $payment->order?->customer_name }}</div>
                <div>{{ $payment->order?->customer_mobile }}</div>
                <div class="text-muted small mt-2">Grand total: Rs. {{ number_format((float) $payment->order?->grand_total, 2) }}</div>
                <a href="{{ route('admin.orders.show', $payment->order) }}" class="btn btn-sm btn-outline-secondary mt-3">View Order</a>
            </div>
        </div>

        @if ($payment->payment_method === 'qr' && ! in_array($payment->payment_status, ['paid', 'refunded', 'cancelled'], true))
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white fw-semibold">Verify Payment</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <textarea name="note" class="form-control mb-2" rows="2" placeholder="Verification note"></textarea>
                        <button class="btn btn-success w-100">Verify</button>
                    </form>
                    <form method="POST" action="{{ route('admin.payments.fail', $payment) }}">
                        @csrf
                        @method('PATCH')
                        <textarea name="failure_reason" class="form-control mb-2" rows="3" placeholder="Failure reason" required></textarea>
                        <button class="btn btn-outline-danger w-100">Mark Failed</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
