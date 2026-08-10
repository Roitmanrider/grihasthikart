@extends('layouts.frontend')
@section('title','Customer Login')
@section('content')
<section class="py-5"><div class="container" style="max-width: 520px;">
<h1 class="h3 mb-3">Customer Login</h1>
<div class="card border-0 shadow-sm"><div class="card-body">
<form method="POST" action="{{ route('customer.login.request') }}" class="row g-3">@csrf
@error('customer')<div class="col-12"><div class="alert alert-warning">{{ $message }}</div></div>@enderror
<div class="col-12"><label class="form-label">Registered Mobile</label><input name="mobile" value="{{ old('mobile', request('mobile')) }}" class="form-control @error('mobile') is-invalid @enderror" required>@error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-12"><button class="btn btn-success w-100">Send OTP</button></div>
</form>
</div></div></div></section>
@endsection
