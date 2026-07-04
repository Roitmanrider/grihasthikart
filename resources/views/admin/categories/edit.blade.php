@extends('layouts.admin')

@section('title','Edit Category')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Edit Category</h1>
    <div class="text-muted">{{ $category->name }}</div>
</div>

<form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.categories.form')
</form>

@endsection
