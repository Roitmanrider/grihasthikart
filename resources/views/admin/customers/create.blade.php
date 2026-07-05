@extends('layouts.admin')
@section('title','Create Customer')
@section('admin-content')
<div class="d-flex justify-content-between mb-4"><h1 class="h3">Create Customer</h1><a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Back</a></div>
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('admin.customers.store') }}" class="row g-3">@csrf @include('admin.customers.form', ['customer' => null])<div class="col-12"><button class="btn btn-success">Create</button></div></form></div></div>
@endsection
