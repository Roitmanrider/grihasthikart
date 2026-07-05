@extends('layouts.admin')

@section('title', 'Create Attribute')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Create Attribute</h1>
    <div class="text-muted">Add a filter or future variant attribute.</div>
</div>

<form method="POST" action="{{ route('admin.attributes.store') }}">
    @include('admin.attributes.form', ['attribute' => null])
</form>

@endsection
