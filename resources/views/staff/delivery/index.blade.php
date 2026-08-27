@extends('layouts.staff')

@section('title', 'My Deliveries')

@section('staff-content')
<h1 class="h3 mb-4">My Deliveries</h1>
<div class="row g-3">
    @forelse ($attempts as $attempt)
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h2 class="h5">{{ $attempt->order?->order_number }}</h2>
                        <span class="badge text-bg-light">{{ str($attempt->status)->headline() }}</span>
                    </div>
                    <div class="small text-muted">{{ $attempt->order?->customer_name }} / {{ $attempt->order?->customer_mobile }}</div>
                    <div class="mt-2">{{ $attempt->order?->delivery_address_line1 }}, {{ $attempt->order?->delivery_city }}</div>
                    <a class="btn btn-sm btn-outline-primary mt-2" target="_blank" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($attempt->order?->delivery_address_line1.' '.$attempt->order?->delivery_city.' '.$attempt->order?->delivery_pincode) }}">Navigate</a>
                    <div class="alert alert-light border mt-3 mb-2 small">Location will be recorded with this delivery update when your device provides it.</div>
                    <form method="POST" action="{{ route('staff.delivery-attempts.verify-otp', $attempt) }}" class="row g-2">
                        @csrf @method('PATCH')
                        <div class="col-8"><input name="otp" class="form-control" placeholder="Enter Delivery OTP" maxlength="6" required></div>
                        <div class="col-4"><button class="btn btn-success w-100">Deliver</button></div>
                    </form>
                    <form method="POST" action="{{ route('staff.delivery-attempts.exception', $attempt) }}" class="row g-2 mt-2">
                        @csrf @method('PATCH')
                        <div class="col-md-5"><select name="event_type" class="form-select"><option value="DELIVERY_FAILED_BY_AGENT">Delivery Failed</option><option value="CUSTOMER_UNAVAILABLE">Customer Unavailable</option><option value="RESCHEDULE_REQUESTED">Reschedule Requested</option></select></div>
                        <div class="col-md-4"><input name="reason_code" class="form-control" placeholder="Reason" required></div>
                        <div class="col-md-3"><button class="btn btn-outline-danger w-100">Record</button></div>
                    </form>
                    <form method="POST" action="{{ route('staff.delivery-attempts.approvals.store', $attempt) }}" class="row g-2 mt-2">
                        @csrf
                        <div class="col-md-4"><select name="approval_type" class="form-select"><option value="RETURN_TO_STORE">Return to Store</option><option value="DELIVERY_OTP_OVERRIDE">OTP Override</option></select></div>
                        <div class="col-md-3"><input name="reason_code" class="form-control" placeholder="Reason" required></div>
                        <div class="col-md-3"><input name="notes" class="form-control" placeholder="Note" required></div>
                        <div class="col-md-2"><button class="btn btn-outline-warning w-100">Request</button></div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">No assigned deliveries.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $attempts->links() }}</div>
@endsection
