@extends('layouts.admin')

@section('title','Categories')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Categories</h1>
        <div class="text-muted">Manage parent and child catalog categories.</div>
    </div>

    <a href="{{ route('admin.categories.create') }}" class="btn btn-success">Add Category</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, slug, SEO">
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
                <label class="form-label">Parent</label>
                <select name="parent_id" class="form-select">
                    <option value="">All</option>
                    <option value="root" @selected(request('parent_id') === 'root')>Root only</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) request('parent_id') === (string) $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
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

            <div class="col-lg-1 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.categories.bulk-action', request()->query()) }}">
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
                            <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.category-check').forEach((el) => el.checked = this.checked)">
                        </th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.categories.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Name</a>
                        </th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.categories.index', array_merge(request()->query(), ['sort' => 'display_order', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Order</a>
                        </th>
                        <th>Children</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr @class(['table-warning' => $category->trashed()])>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $category->id }}" class="form-check-input category-check">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $category->name }}</div>
                                <div class="small text-muted">{{ $category->slug }}</div>
                            </td>
                            <td>{{ $category->parent?->name ?? 'Root' }}</td>
                            <td>
                                <span class="badge {{ $category->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $category->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $category->is_featured ? 'Yes' : 'No' }}</td>
                            <td>{{ $category->display_order }}</td>
                            <td>{{ $category->children_count }}</td>
                            <td class="text-end">
                                @if (! $category->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($category->trashed())
                                    <form method="POST" action="{{ route('admin.categories.restore', $category->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $categories->links() }}
        </div>
    </div>
</form>

@endsection
