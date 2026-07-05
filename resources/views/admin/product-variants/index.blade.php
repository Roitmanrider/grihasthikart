@extends('layouts.admin')

@section('title','Product Variants')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Variants</h1>
        <div class="text-muted">{{ $product->name }} / {{ $product->slug }}</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Products</a>
        <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-success">Add Variant</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><span class="fw-semibold">Brand:</span> {{ $product->brand?->name ?? 'None' }}</div>
            <div class="col-md-8">
                <span class="fw-semibold">Categories:</span>
                @foreach ($product->categories as $category)
                    <span class="badge text-bg-light border">{{ $category->name }}{{ $category->pivot->is_primary ? ' *' : '' }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.variants.index', $product) }}" class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Variant, SKU, barcode">
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
                <label class="form-label">Default</label>
                <select name="is_default" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_default') === '1')>Default</option>
                    <option value="0" @selected(request('is_default') === '0')>Not Default</option>
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

<form method="POST" action="{{ route('admin.products.variants.bulk-action', array_merge([$product], request()->query())) }}">
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
                        <th style="width: 40px;"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.variant-check').forEach((el) => el.checked = this.checked)"></th>
                        <th>Variant</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Attributes</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($variants as $variant)
                        <tr @class(['table-warning' => $variant->trashed()])>
                            <td><input type="checkbox" name="ids[]" value="{{ $variant->id }}" class="form-check-input variant-check"></td>
                            <td>
                                <div class="fw-semibold">{{ $variant->variant_name }}</div>
                                <div class="small text-muted">{{ $variant->barcode ?? 'No barcode' }}</div>
                            </td>
                            <td>{{ $variant->sku }}</td>
                            <td>
                                <div>MRP: {{ $variant->mrp }}</div>
                                <div class="fw-semibold">Sale: {{ $variant->selling_price }}</div>
                            </td>
                            <td>
                                @forelse ($variant->attributeValues as $value)
                                    <span class="badge text-bg-light border">{{ $value->attribute?->name }}: {{ $value->value }}</span>
                                @empty
                                    <span class="text-muted small">Default</span>
                                @endforelse
                            </td>
                            <td><span class="badge {{ $variant->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $variant->status ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $variant->is_default ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                @if (! $variant->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.products.variants.show', [$product, $variant]) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.products.variants.edit', [$product, $variant]) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($variant->trashed())
                                    <form method="POST" action="{{ route('admin.products.variants.restore', [$product, $variant->id]) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" class="d-inline" onsubmit="return confirm('Delete this variant?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No variants found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">{{ $variants->links() }}</div>
    </div>
</form>

@endsection
