@extends('layouts.admin')

@section('title', 'Edit Attribute')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Attribute</h1>
    <div class="text-muted">{{ $attribute->name }}</div>
</div>

<form method="POST" action="{{ route('admin.attributes.update', $attribute) }}">
    @method('PUT')
    @include('admin.attributes.form')
</form>

@endsection
