@extends('layouts.admin')
@section('title','Cashback Redemption')
@section('admin-content')
<h1 class="h3 mb-4">Redemption #{{ $redemption->id }}</h1>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row g-4"><div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-body"><div class="fw-semibold">{{ $redemption->customer?->name }}</div><div>Requested: Rs. {{ number_format((float)$redemption->requested_amount,2) }}</div><div>Status: {{ $redemption->status }}</div><div>Coupon: {{ $redemption->coupon?->code ?: '-' }}</div></div></div></div><div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-body">
@if($redemption->status === 'pending')<form method="POST" action="{{ route('admin.cashback.redemptions.approve',$redemption) }}" class="mb-3">@csrf @method('PATCH')<input type="number" step="0.01" name="approved_amount" value="{{ $redemption->requested_amount }}" class="form-control mb-2"><textarea name="admin_note" class="form-control mb-2" placeholder="Admin note"></textarea><button class="btn btn-success">Approve</button></form><form method="POST" action="{{ route('admin.cashback.redemptions.reject',$redemption) }}">@csrf @method('PATCH')<textarea name="admin_note" class="form-control mb-2" required placeholder="Reject reason"></textarea><button class="btn btn-outline-danger">Reject</button></form>@endif
@if($redemption->status === 'approved')<form method="POST" action="{{ route('admin.cashback.redemptions.generate-coupon',$redemption) }}">@csrf<button class="btn btn-success">Generate Coupon</button></form>@endif
</div></div></div></div>
@endsection
