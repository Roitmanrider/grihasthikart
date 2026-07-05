@extends('layouts.admin')

@section('title', 'Edit Coupon')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Edit Coupon</h1>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.coupons.update', $coupon) }}">
    @method('PATCH')
    @include('admin.coupons.form')
</form>
@endsection
