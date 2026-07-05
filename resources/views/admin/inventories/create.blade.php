@extends('layouts.admin')

@section('title','Create Inventory')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Create Inventory</h1>
        <div class="text-muted">Create stock for one product variant at one location.</div>
    </div>

    <a href="{{ route('admin.inventories.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inventories.store') }}" class="row g-3">
            @csrf
            @include('admin.inventories.form', ['inventory' => null, 'options' => $options])
            <div class="col-12">
                <button class="btn btn-success">Create Inventory</button>
            </div>
        </form>
    </div>
</div>

@endsection
