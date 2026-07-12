@extends('layouts.admin')

@section('title', 'Edit Supplier')

@section('admin-content')
<div class="mb-4">
    <h1 class="h3 mb-1">Edit Supplier</h1>
    <div class="text-muted">{{ $supplier->name }}</div>
</div>

<form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
    @method('PATCH')
    @include('admin.suppliers.form')
</form>
@endsection
