@extends('layouts.admin')

@section('title', 'Attribute Values')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Attribute Values</h1>
        <div class="text-muted">Manage selectable values for catalog attributes.</div>
    </div>

    <a href="{{ route('admin.attribute-values.create') }}" class="btn btn-success">Add Attribute Value</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.attribute-values.index') }}" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Value, slug, attribute">
            </div>

            <div class="col-lg-3">
                <label class="form-label">Attribute</label>
                <select name="attribute_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($attributes as $attribute)
                        <option value="{{ $attribute->id }}" @selected((string) request('attribute_id') === (string) $attribute->id)>{{ $attribute->name }}</option>
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

<form method="POST" action="{{ route('admin.attribute-values.bulk-action', request()->query()) }}">
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
                            <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.attribute-value-check').forEach((el) => el.checked = this.checked)">
                        </th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.attribute-values.index', array_merge(request()->query(), ['sort' => 'value', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Value</a>
                        </th>
                        <th>Attribute</th>
                        <th>Status</th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.attribute-values.index', array_merge(request()->query(), ['sort' => 'display_order', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Order</a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attributeValues as $attributeValue)
                        <tr @class(['table-warning' => $attributeValue->trashed()])>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $attributeValue->id }}" class="form-check-input attribute-value-check">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $attributeValue->value }}</div>
                                <div class="small text-muted">{{ $attributeValue->slug }}</div>
                            </td>
                            <td>{{ $attributeValue->attribute?->name ?? 'Not set' }}</td>
                            <td>
                                <span class="badge {{ $attributeValue->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $attributeValue->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $attributeValue->display_order }}</td>
                            <td class="text-end">
                                @if (! $attributeValue->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.attribute-values.show', $attributeValue) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.attribute-values.edit', $attributeValue) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($attributeValue->trashed())
                                    <form method="POST" action="{{ route('admin.attribute-values.restore', $attributeValue->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.attribute-values.destroy', $attributeValue) }}" class="d-inline" onsubmit="return confirm('Delete this attribute value?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No attribute values found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $attributeValues->links() }}
        </div>
    </div>
</form>

@endsection
