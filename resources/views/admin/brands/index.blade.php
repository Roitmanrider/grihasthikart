@extends('layouts.admin')

@section('title', 'Brands')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Brands</h1>
        <div class="text-muted">Manage grocery and FMCG product brands.</div>
    </div>

    <a href="{{ route('admin.brands.create') }}" class="btn btn-success">Add Brand</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.brands.index') }}" class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, slug, website, SEO">
            </div>

            <div class="col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('status') === '1')>Active</option>
                    <option value="0" @selected(request('status') === '0')>Inactive</option>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">Featured</label>
                <select name="is_featured" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_featured') === '1')>Featured</option>
                    <option value="0" @selected(request('is_featured') === '0')>Not Featured</option>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">Deleted</label>
                <select name="trashed" class="form-select">
                    <option value="">Without deleted</option>
                    <option value="with" @selected(request('trashed') === 'with')>With deleted</option>
                    <option value="only" @selected(request('trashed') === 'only')>Deleted only</option>
                </select>
            </div>

            <div class="col-lg-2 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.brands.bulk-action', request()->query()) }}">
    @csrf

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="action" class="form-select">
                        <option value="">Bulk action</option>
                        <option value="activate">Mark Active</option>
                        <option value="deactivate">Mark Inactive</option>
                        <option value="delete">Delete</option>
                        <option value="restore">Restore</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-outline-secondary">Apply</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.brand-check').forEach((el) => el.checked = this.checked)">
                        </th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.brands.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Name</a>
                        </th>
                        <th>Website</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.brands.index', array_merge(request()->query(), ['sort' => 'display_order', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Order</a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr @class(['table-warning' => $brand->trashed()])>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $brand->id }}" class="form-check-input brand-check">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $brand->name }}</div>
                                <div class="small text-muted">{{ $brand->slug }}</div>
                            </td>
                            <td>
                                @if ($brand->website_url)
                                    <a href="{{ $brand->website_url }}" target="_blank" rel="noopener" class="text-decoration-none">{{ $brand->website_url }}</a>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $brand->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $brand->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $brand->is_featured ? 'Yes' : 'No' }}</td>
                            <td>{{ $brand->display_order }}</td>
                            <td class="text-end">
                                @if (! $brand->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.brands.show', $brand) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($brand->trashed())
                                    <form method="POST" action="{{ route('admin.brands.restore', $brand->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="d-inline" onsubmit="return confirm('Delete this brand?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No brands found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $brands->links() }}
        </div>
    </div>
</form>

@endsection
