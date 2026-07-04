@extends('layouts.admin')

@section('title', 'View Brand')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $brand->name }}</h1>
        <div class="text-muted">{{ $brand->slug }}</div>
    </div>

    <div class="btn-group">
        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-success">Edit</a>
        <a href="{{ route('admin.brands.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Brand Information</h2>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9">{{ $brand->name }}</dd>

                    <dt class="col-sm-3">Website</dt>
                    <dd class="col-sm-9">
                        @if ($brand->website_url)
                            <a href="{{ $brand->website_url }}" target="_blank" rel="noopener">{{ $brand->website_url }}</a>
                        @else
                            <span class="text-muted">Not set</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $brand->description ?: 'Not set' }}</dd>
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
                    <dd class="col-sm-9">{{ $brand->meta_title ?: 'Not set' }}</dd>

                    <dt class="col-sm-3">Meta Description</dt>
                    <dd class="col-sm-9">{{ $brand->meta_description ?: 'Not set' }}</dd>

                    <dt class="col-sm-3">Meta Keywords</dt>
                    <dd class="col-sm-9">{{ $brand->meta_keywords ?: 'Not set' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Status</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge {{ $brand->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $brand->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mb-3">
                    <span class="fw-semibold">Featured:</span> {{ $brand->is_featured ? 'Yes' : 'No' }}
                </div>

                <div>
                    <span class="fw-semibold">Display Order:</span> {{ $brand->display_order }}
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Media</h2>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="fw-semibold">Logo:</span>
                    <div class="small text-muted">{{ $brand->logo ?: 'Not set' }}</div>
                </div>

                <div>
                    <span class="fw-semibold">Banner:</span>
                    <div class="small text-muted">{{ $brand->banner ?: 'Not set' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
