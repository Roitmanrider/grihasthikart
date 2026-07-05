@extends('layouts.admin')

@section('title','Create Product')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Create Product</h1>
    <div class="text-muted">Add catalog and display details.</div>
</div>

<form method="POST" action="{{ route('admin.products.store') }}">
    @csrf

    @include('admin.products.form', ['buttonText' => 'Create Product'])
</form>

@endsection
