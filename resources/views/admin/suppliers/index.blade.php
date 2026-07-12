@extends('layouts.admin')

@section('title', 'Suppliers')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Suppliers</h1>
        <div class="text-muted">Manage supplier details used in purchases and reports.</div>
    </div>
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-success">Add Supplier</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.suppliers.index') }}" class="row g-3">
            <div class="col-lg-5">
                <label class="form-label">Search</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, contact, mobile, email, GSTIN">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button class="btn btn-outline-success w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>GSTIN</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Purchases</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $supplier->name }}</div>
                            <div class="small text-muted">{{ $supplier->email ?: 'No email' }}</div>
                        </td>
                        <td>
                            <div>{{ $supplier->contact_person ?: 'Not set' }}</div>
                            <div class="small text-muted">{{ $supplier->mobile ?: 'No mobile' }}</div>
                        </td>
                        <td>{{ $supplier->gstin ?: 'Not set' }}</td>
                        <td>{{ collect([$supplier->city, $supplier->state])->filter()->join(', ') ?: 'Not set' }}</td>
                        <td><span class="badge {{ $supplier->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($supplier->status) }}</span></td>
                        <td>{{ $supplier->purchase_entries_count }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.suppliers.show', $supplier) }}" class="btn btn-outline-secondary">View</a>
                                <a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-outline-success">Edit</a>
                            </div>
                            <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="d-inline" onsubmit="return confirm('Delete this supplier?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No suppliers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $suppliers->links() }}</div>
</div>
@endsection
