@extends('layouts.admin')

@section('title', 'Create Brand')

@section('admin-content')

<div class="mb-4">
    <h1 class="h3 mb-1">Create Brand</h1>
    <div class="text-muted">Add a grocery or FMCG brand.</div>
</div>

<form method="POST" action="{{ route('admin.brands.store') }}" enctype="multipart/form-data">
    @include('admin.brands.form', ['brand' => null])
</form>

@endsection
