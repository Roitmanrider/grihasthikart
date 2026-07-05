@extends('layouts.admin')
@section('title','Create Cashback Rule')
@section('admin-content')
<h1 class="h3 mb-4">Create Cashback Rule</h1>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.cashback.rules.store') }}">@include('admin.cashback.rules.form')</form>
@endsection
