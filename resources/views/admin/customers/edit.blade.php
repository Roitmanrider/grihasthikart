@extends('layouts.admin')
@section('title','Edit Customer')
@section('admin-content')
<div class="d-flex justify-content-between mb-4"><h1 class="h3">Edit Customer</h1><a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-secondary">Back</a></div>
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="row g-3">@csrf @method('PATCH') @include('admin.customers.form', ['customer' => $customer])<div class="col-12"><button class="btn btn-success">Update</button></div></form></div></div>
@endsection
