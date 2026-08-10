@extends('layouts.frontend')
@section('title','Verify OTP')
@section('content')
<section class="py-5"><div class="container" style="max-width: 520px;">
<h1 class="h3 mb-3">Verify OTP</h1>
<div class="card border-0 shadow-sm"><div class="card-body">
<form method="POST" action="{{ route('customer.otp.verify') }}" class="row g-3">@csrf
@error('customer')<div class="col-12"><div class="alert alert-warning">{{ $message }}</div></div>@enderror
<div class="col-12"><label class="form-label">Mobile</label><input name="mobile" value="{{ old('mobile', request('mobile')) }}" class="form-control @error('mobile') is-invalid @enderror" required>@error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><label class="form-label">OTP</label><input name="otp" value="{{ old('otp') }}" class="form-control @error('otp') is-invalid @enderror" required>@error('otp')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><button class="btn btn-success w-100">Login</button></div>
</form>
</div></div></div></section>
@endsection
