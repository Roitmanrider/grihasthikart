@extends('layouts.admin')

@section('title','Edit Inventory')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Edit Inventory</h1>
        <div class="text-muted">{{ $inventory->productVariant?->product?->name }} / {{ $inventory->productVariant?->sku }}</div>
    </div>

    <a href="{{ route('admin.inventories.show', $inventory) }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inventories.update', $inventory) }}" class="row g-3">
            @csrf
            @method('PUT')
            @include('admin.inventories.form', ['inventory' => $inventory, 'options' => null])
            <div class="col-12">
                <button class="btn btn-success">Update Inventory</button>
            </div>
        </form>
    </div>
</div>

@endsection
