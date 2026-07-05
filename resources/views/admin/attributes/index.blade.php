@extends('layouts.admin')

@section('title', 'Attributes')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Attributes</h1>
        <div class="text-muted">Manage catalog attributes for filters and future product variants.</div>
    </div>

    <a href="{{ route('admin.attributes.create') }}" class="btn btn-success">Add Attribute</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.attributes.index') }}" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, slug, type">
            </div>

            <div class="col-lg-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ str($type)->headline() }}</option>
                    @endforeach
                </select>
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
                <label class="form-label">Usage</label>
                <select name="is_variant_defining" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_variant_defining') === '1')>Variant defining</option>
                    <option value="0" @selected(request('is_variant_defining') === '0')>Not variant defining</option>
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

<form method="POST" action="{{ route('admin.attributes.bulk-action', request()->query()) }}">
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
                            <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.attribute-check').forEach((el) => el.checked = this.checked)">
                        </th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.attributes.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Name</a>
                        </th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.attributes.index', array_merge(request()->query(), ['sort' => 'type', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Type</a>
                        </th>
                        <th>Status</th>
                        <th>Filterable</th>
                        <th>Variant Defining</th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.attributes.index', array_merge(request()->query(), ['sort' => 'display_order', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Order</a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attributes as $attribute)
                        <tr @class(['table-warning' => $attribute->trashed()])>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $attribute->id }}" class="form-check-input attribute-check">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $attribute->name }}</div>
                                <div class="small text-muted">{{ $attribute->slug }}</div>
                            </td>
                            <td>{{ str($attribute->type)->headline() }}</td>
                            <td>
                                <span class="badge {{ $attribute->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $attribute->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $attribute->is_filterable ? 'Yes' : 'No' }}</td>
                            <td>{{ $attribute->is_variant_defining ? 'Yes' : 'No' }}</td>
                            <td>{{ $attribute->display_order }}</td>
                            <td class="text-end">
                                @if (! $attribute->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.attributes.show', $attribute) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($attribute->trashed())
                                    <form method="POST" action="{{ route('admin.attributes.restore', $attribute->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}" class="d-inline" onsubmit="return confirm('Delete this attribute?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No attributes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $attributes->links() }}
        </div>
    </div>
</form>

@endsection
