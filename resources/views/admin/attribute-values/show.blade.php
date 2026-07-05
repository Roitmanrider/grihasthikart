@extends('layouts.admin')

@section('title', 'View Attribute Value')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $attributeValue->value }}</h1>
        <div class="text-muted">{{ $attributeValue->slug }}</div>
    </div>

    <div class="btn-group">
        <a href="{{ route('admin.attribute-values.edit', $attributeValue) }}" class="btn btn-success">Edit</a>
        <a href="{{ route('admin.attribute-values.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Attribute Value Information</h2>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Attribute</dt>
            <dd class="col-sm-9">{{ $attributeValue->attribute?->name ?? 'Not set' }}</dd>

            <dt class="col-sm-3">Value</dt>
            <dd class="col-sm-9">{{ $attributeValue->value }}</dd>

            <dt class="col-sm-3">Slug</dt>
            <dd class="col-sm-9">{{ $attributeValue->slug }}</dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <span class="badge {{ $attributeValue->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                    {{ $attributeValue->status ? 'Active' : 'Inactive' }}
                </span>
            </dd>

            <dt class="col-sm-3">Display Order</dt>
            <dd class="col-sm-9">{{ $attributeValue->display_order }}</dd>
        </dl>
    </div>
</div>

@endsection
