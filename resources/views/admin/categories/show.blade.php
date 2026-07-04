@extends('layouts.admin')

@section('title','Category Details')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $category->name }}</h1>
        <div class="text-muted">{{ $category->slug }}</div>
    </div>

    <div class="btn-group">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Back</a>
        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-success">Edit</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Overview</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Parent</dt>
                    <dd class="col-sm-9">{{ $category->parent?->name ?? 'Root category' }}</dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $category->description ?: 'Not provided' }}</dd>

                    <dt class="col-sm-3">Display Order</dt>
                    <dd class="col-sm-9">{{ $category->display_order }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ $category->status ? 'Active' : 'Inactive' }}</dd>

                    <dt class="col-sm-3">Featured</dt>
                    <dd class="col-sm-9">{{ $category->is_featured ? 'Yes' : 'No' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">SEO</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Meta Title</dt>
                    <dd class="col-sm-9">{{ $category->meta_title ?: 'Not provided' }}</dd>

                    <dt class="col-sm-3">Meta Description</dt>
                    <dd class="col-sm-9">{{ $category->meta_description ?: 'Not provided' }}</dd>

                    <dt class="col-sm-3">Meta Keywords</dt>
                    <dd class="col-sm-9">{{ $category->meta_keywords ?: 'Not provided' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Media</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-semibold">Image</div>
                    <div class="text-muted small">{{ $category->image ?: 'Not uploaded' }}</div>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold">Banner</div>
                    <div class="text-muted small">{{ $category->banner ?: 'Not uploaded' }}</div>
                </div>

                <div>
                    <div class="fw-semibold">Icon</div>
                    <div class="text-muted small">{{ $category->icon ?: 'Not provided' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
