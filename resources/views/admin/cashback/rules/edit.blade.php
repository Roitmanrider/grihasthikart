@extends('layouts.admin')
@section('title','Edit Cashback Rule')
@section('admin-content')
<h1 class="h3 mb-4">Edit Cashback Rule</h1>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('admin.cashback.rules.update',$rule) }}">@method('PATCH') @include('admin.cashback.rules.form')</form>
@endsection
