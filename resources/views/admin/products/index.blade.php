@extends('layouts.admin')

@section('title','Products')

@section('admin-content')

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Products</h1>
        <div class="text-muted">Manage catalog products and merchandising metadata.</div>
    </div>

    <a href="{{ route('admin.products.create') }}" class="btn btn-success">Add Product</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-3">
            <div class="col-lg-3">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, brand, category, barcode">
            </div>

            <div class="col-lg-2">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) request('brand_id') === (string) $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
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
                <label class="form-label">Flag</label>
                <select name="is_featured" class="form-select">
                    <option value="">Any</option>
                    <option value="1" @selected(request('is_featured') === '1')>Featured</option>
                    <option value="0" @selected(request('is_featured') === '0')>Not Featured</option>
                </select>
            </div>

            <div class="col-lg-1 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>

            <div class="col-lg-2">
                <label class="form-label">Trending</label>
                <select name="is_trending" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_trending') === '1')>Yes</option>
                    <option value="0" @selected(request('is_trending') === '0')>No</option>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">Popular</label>
                <select name="is_popular" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_popular') === '1')>Yes</option>
                    <option value="0" @selected(request('is_popular') === '0')>No</option>
                </select>
            </div>

            <div class="col-lg-2">
                <label class="form-label">New Arrival</label>
                <select name="is_new_arrival" class="form-select">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_new_arrival') === '1')>Yes</option>
                    <option value="0" @selected(request('is_new_arrival') === '0')>No</option>
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
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.products.bulk-action', request()->query()) }}">
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
                            <input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.product-check').forEach((el) => el.checked = this.checked)">
                        </th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.products.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Name</a>
                        </th>
                        <th>Brand</th>
                        <th>Categories</th>
                        <th>Status</th>
                        <th>Flags</th>
                        <th>
                            <a class="text-decoration-none text-dark" href="{{ route('admin.products.index', array_merge(request()->query(), ['sort' => 'display_order', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">Order</a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr @class(['table-warning' => $product->trashed()])>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $product->id }}" class="form-check-input product-check">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="small text-muted">{{ $product->slug }}</div>
                            </td>
                            <td>{{ $product->brand?->name ?? 'None' }}</td>
                            <td>
                                @foreach ($product->categories as $category)
                                    <span class="badge text-bg-light border">{{ $category->name }}{{ $category->pivot->is_primary ? ' *' : '' }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge {{ $product->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $product->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="small">
                                @if ($product->is_featured) Featured @endif
                                @if ($product->is_trending) Trending @endif
                                @if ($product->is_popular) Popular @endif
                                @if ($product->is_new_arrival) New @endif
                            </td>
                            <td>{{ $product->display_order }}</td>
                            <td class="text-end">
                                @if (! $product->trashed())
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-success">Edit</a>
                                    </div>
                                @endif

                                @if ($product->trashed())
                                    <form method="POST" action="{{ route('admin.products.restore', $product->id) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">Restore</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $products->links() }}
        </div>
    </div>
</form>

@endsection
