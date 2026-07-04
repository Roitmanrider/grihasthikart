@extends('layouts.admin')

@section('title','Create Category')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Create Category</h1>
    <div class="text-muted">Add a parent or child category.</div>
</div>

<form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
    @include('admin.categories.form', ['category' => null])
</form>

@endsection
