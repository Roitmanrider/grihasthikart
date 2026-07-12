@extends('layouts.admin')

@section('title', 'Create Supplier')

@section('admin-content')
<div class="mb-4">
    <h1 class="h3 mb-1">Create Supplier</h1>
    <div class="text-muted">Add supplier details for purchase operations.</div>
</div>

<form method="POST" action="{{ route('admin.suppliers.store') }}">
    @include('admin.suppliers.form', ['supplier' => null])
</form>
@endsection
