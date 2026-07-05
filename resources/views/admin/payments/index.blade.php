@extends('layouts.admin')

@section('title', 'Payments')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Payments</h1>
        <div class="text-muted">Review customer payments and QR/manual verification.</div>
    </div>
</div>

<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body row g-3">
        <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search payment, order, customer, mobile"></div>
        <div class="col-md-2">
            <select name="payment_method" class="form-select">
                <option value="">All methods</option>
                @foreach (\App\Models\Payment::METHODS as $method)
                    <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ strtoupper($method) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="payment_status" class="form-select">
                <option value="">All statuses</option>
                @foreach (\App\Models\Payment::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-12"><button class="btn btn-success">Filter</button></div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Payment</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td class="fw-semibold">{{ $payment->payment_number }}</td>
                        <td>{{ $payment->order?->order_number }}</td>
                        <td>{{ $payment->order?->customer_name }}<div class="small text-muted">{{ $payment->order?->customer_mobile }}</div></td>
                        <td>{{ strtoupper($payment->payment_method) }}</td>
                        <td><span class="badge text-bg-light border">{{ str_replace('_', ' ', $payment->payment_status) }}</span></td>
                        <td>Rs. {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->created_at?->format('d M Y') }}</td>
                        <td><a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-success">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $payments->links() }}</div>
</div>
@endsection
