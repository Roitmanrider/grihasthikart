@extends('layouts.admin')

@section('title','Create Product Variant')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Create Variant</h1>
    <div class="text-muted">{{ $product->name }}</div>
</div>

<form method="POST" action="{{ route('admin.products.variants.store', $product) }}">
    @csrf

    @include('admin.product-variants.form', ['buttonText' => 'Create Variant'])
</form>

@endsection
