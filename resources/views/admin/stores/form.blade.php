@extends('layouts.admin')

@section('title', $store->exists ? 'Edit Store' : 'Add Store')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ $store->exists ? 'Edit Store' : 'Add Store' }}</h1>
        <div class="text-muted">Stores are stock locations used for store-scoped inventory, orders, and pricing.</div>
    </div>
    <a href="{{ route('admin.stores.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ $store->exists ? route('admin.stores.update', $store) : route('admin.stores.store') }}" class="card border-0 shadow-sm">
    @csrf
    @if ($store->exists)
        @method('PUT')
    @endif

    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Store Name</label>
            <input name="name" value="{{ old('name', $store->name) }}" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Code</label>
            <input name="code" value="{{ old('code', $store->code) }}" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Display Order</label>
            <input type="number" min="0" name="display_order" value="{{ old('display_order', $store->display_order ?? 0) }}" class="form-control">
        </div>
        <div class="col-md-12">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $store->address) }}</textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label">City</label>
            <input name="city" value="{{ old('city', $store->city) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">State</label>
            <input name="state" value="{{ old('state', $store->state) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Pincode</label>
            <input name="pincode" value="{{ old('pincode', $store->pincode) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Manager</label>
            <input name="manager_name" value="{{ old('manager_name', $store->manager_name) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input name="phone" value="{{ old('phone', $store->phone) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $store->email) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4">
                <input type="hidden" name="status" value="0">
                <input class="form-check-input" type="checkbox" name="status" value="1" id="status" @checked(old('status', $store->status ?? true))>
                <label class="form-check-label" for="status">Active</label>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4">
                <input type="hidden" name="accepts_online_orders" value="0">
                <input class="form-check-input" type="checkbox" name="accepts_online_orders" value="1" id="accepts_online_orders" @checked(old('accepts_online_orders', $store->accepts_online_orders ?? true))>
                <label class="form-check-label" for="accepts_online_orders">Accept Online Orders</label>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">{{ $store->exists ? 'Update Store' : 'Create Store' }}</button>
    </div>
</form>
@endsection
