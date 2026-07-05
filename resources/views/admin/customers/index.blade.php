@extends('layouts.admin')

@section('title','Customers')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Customers</h1><div class="text-muted">Admin-created customer accounts.</div></div>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-success">Add Customer</a>
</div>

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <form method="GET" class="row g-3">
        <div class="col-md-4"><label class="form-label">Search</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, mobile, email"></div>
        <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All</option><option value="1" @selected(request('status')==='1')>Active</option><option value="0" @selected(request('status')==='0')>Inactive</option></select></div>
        <div class="col-md-2"><label class="form-label">Premium</label><select name="is_premium" class="form-select"><option value="">All</option><option value="1" @selected(request('is_premium')==='1')>Premium</option><option value="0" @selected(request('is_premium')==='0')>Regular</option></select></div>
        <div class="col-md-2"><label class="form-label">Deleted</label><select name="trashed" class="form-select"><option value="">Without deleted</option><option value="with" @selected(request('trashed')==='with')>With deleted</option><option value="only" @selected(request('trashed')==='only')>Deleted only</option></select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-success w-100">Filter</button></div>
    </form>
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Status</th><th>Premium</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($customers as $customer)
            <tr @class(['table-warning'=>$customer->trashed()])>
                <td class="fw-semibold">{{ $customer->name }}</td><td>{{ $customer->mobile }}</td><td>{{ $customer->email ?: 'None' }}</td>
                <td><span class="badge {{ $customer->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $customer->status ? 'Active' : 'Inactive' }}</span></td>
                <td>{{ $customer->is_premium ? 'Yes' : 'No' }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    @if (! $customer->trashed())<a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-outline-success">Edit</a>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No customers found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div><div class="card-footer bg-white">{{ $customers->links() }}</div></div>
@endsection
