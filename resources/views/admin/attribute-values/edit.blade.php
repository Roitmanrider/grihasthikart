@extends('layouts.admin')

@section('title', 'Edit Attribute Value')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Attribute Value</h1>
    <div class="text-muted">{{ $attributeValue->value }}</div>
</div>

<form method="POST" action="{{ route('admin.attribute-values.update', $attributeValue) }}">
    @method('PUT')
    @include('admin.attribute-values.form')
</form>

@endsection
