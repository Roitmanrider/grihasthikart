@extends('layouts.admin')

@section('title', 'View Attribute')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $attribute->name }}</h1>
        <div class="text-muted">{{ $attribute->slug }}</div>
    </div>

    <div class="btn-group">
        <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-success">Edit</a>
        <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Attribute Information</h2>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $attribute->name }}</dd>

            <dt class="col-sm-3">Type</dt>
            <dd class="col-sm-9">{{ str($attribute->type)->headline() }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge {{ $attribute->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                    {{ $attribute->status ? 'Active' : 'Inactive' }}
                </span>
            </dd>

            <dt class="col-sm-3">Filterable</dt>
            <dd class="col-sm-9">{{ $attribute->is_filterable ? 'Yes' : 'No' }}</dd>

            <dt class="col-sm-3">Variant Defining</dt>
            <dd class="col-sm-9">{{ $attribute->is_variant_defining ? 'Yes' : 'No' }}</dd>

            <dt class="col-sm-3">Display Order</dt>
            <dd class="col-sm-9">{{ $attribute->display_order }}</dd>
        </dl>
    </div>
</div>

@endsection
