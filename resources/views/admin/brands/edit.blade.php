@extends('layouts.admin')

@section('title', 'Edit Brand')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Brand</h1>
    <div class="text-muted">{{ $brand->name }}</div>
</div>

<form method="POST" action="{{ route('admin.brands.update', $brand) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.brands.form')
</form>

@endsection
