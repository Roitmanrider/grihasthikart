@extends('layouts.admin')

@section('title', 'Edit Staff')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Edit Staff</h1>
        <div class="text-muted">{{ $staff->name }} / {{ $staff->email }}</div>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="card border-0 shadow-sm">
    @csrf
    @method('PATCH')
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="">Unassigned</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $staff->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Assigned Store</label>
            <select name="assigned_store_id" class="form-select">
                <option value="">All Stores / Super Admin only</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) old('assigned_store_id', $staff->assigned_store_id) === (string) $store->id)>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">Update Staff</button>
    </div>
</form>
@endsection
