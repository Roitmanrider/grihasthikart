@extends('layouts.admin')

@section('title', 'Stores')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Stores</h1>
        <div class="text-muted">Manage operational stores backed by stock locations.</div>
    </div>
    <a href="{{ route('admin.stores.create') }}" class="btn btn-success">Add Store</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Online Orders</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stores as $store)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $store->name }}</div>
                            @if ($store->is_default)
                                <span class="badge text-bg-primary">Default</span>
                            @endif
                        </td>
                        <td>{{ $store->code }}</td>
                        <td>{{ $store->city ?: 'Not set' }}</td>
                        <td><span class="badge {{ $store->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $store->status ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $store->accepts_online_orders ? 'Enabled' : 'Disabled' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.stores.edit', $store) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @unless ($store->is_default)
                                <form method="POST" action="{{ route('admin.stores.destroy', $store) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No stores found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $stores->links() }}</div>
@endsection
