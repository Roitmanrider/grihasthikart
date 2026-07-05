@extends('layouts.admin')

@section('title','Edit Product')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Product</h1>
    <div class="text-muted">{{ $product->name }}</div>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}">
    @csrf
    @method('PUT')

    @include('admin.products.form', ['buttonText' => 'Update Product'])
</form>

@include('admin.product-images.partials.manager', ['product' => $product])

@endsection
