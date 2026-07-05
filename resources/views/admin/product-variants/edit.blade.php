@extends('layouts.admin')

@section('title','Edit Product Variant')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Variant</h1>
    <div class="text-muted">{{ $product->name }} / {{ $productVariant->variant_name }}</div>
</div>

<form method="POST" action="{{ route('admin.products.variants.update', [$product, $productVariant]) }}">
    @csrf
    @method('PUT')

    @include('admin.product-variants.form', ['buttonText' => 'Update Variant'])
</form>

@endsection
