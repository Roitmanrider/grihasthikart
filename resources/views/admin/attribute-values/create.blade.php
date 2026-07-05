@extends('layouts.admin')

@section('title', 'Create Attribute Value')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Create Attribute Value</h1>
    <div class="text-muted">Add a value under an existing attribute.</div>
</div>

<form method="POST" action="{{ route('admin.attribute-values.store') }}">
    @include('admin.attribute-values.form', ['attributeValue' => null])
</form>

@endsection
