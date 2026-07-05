@extends('layouts.admin')

@section('title', 'Create Coupon')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0">Create Coupon</h1>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.coupons.store') }}">
    @include('admin.coupons.form', ['coupon' => new \App\Models\Coupon])
</form>
@endsection
