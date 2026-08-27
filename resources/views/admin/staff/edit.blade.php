@extends('layouts.admin')

@section('title', ($creating ?? false) ? 'Add Staff' : 'Edit Staff')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">{{ ($creating ?? false) ? 'Add Staff' : 'Edit Staff' }}</h1>
        <div class="text-muted">{{ ($creating ?? false) ? 'Create a user account for Staff Portal access.' : $staff->name.' / '.$staff->email }}</div>
    </div>
    <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ ($creating ?? false) ? route('admin.staff.store') : route('admin.staff.update', $staff) }}" class="card border-0 shadow-sm">
    @csrf
    @unless ($creating ?? false)
        @method('PATCH')
    @endunless
    <div class="card-body row g-3">
        @if ($creating ?? false)
            <div class="col-md-4">
                <label class="form-label">Employee Name</label>
                <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Temporary Password</label>
                <input name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        @endif
        <div class="col-md-6">
            <label class="form-label">Legacy Primary Role</label>
            <select name="role" class="form-select">
                <option value="">Unassigned</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $staff->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-text">Keep Super Admin here for full admin access. Operational staff should use role bundles below.</div>
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
        <div class="col-12">
            <label class="form-label">Operational Role Bundles</label>
            <div class="row g-2">
                @foreach ($staffRoles as $value => $label)
                    <div class="col-md-4">
                        <label class="border rounded p-2 w-100">
                            <input type="checkbox" name="staff_roles[]" value="{{ $value }}" @checked(in_array($value, old('staff_roles', $staff->staff_roles ?? []), true))>
                            {{ $label }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Approval Authority</label>
            <div class="row g-2">
                @foreach ($approvalPermissions as $value => $label)
                    <div class="col-md-4">
                        <label class="border rounded p-2 w-100">
                            <input type="checkbox" name="additional_permissions[]" value="{{ $value }}" @checked(in_array($value, old('additional_permissions', $staff->additional_permissions ?? []), true))>
                            {{ $label }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Additional Advanced Permissions</label>
            <select name="additional_permissions[]" class="form-select" multiple size="8">
                @foreach ($allPermissions as $permission)
                    <option value="{{ $permission }}" @selected(in_array($permission, old('additional_permissions', $staff->additional_permissions ?? []), true))>{{ $permission }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Denied Permission Overrides</label>
            <select name="denied_permissions[]" class="form-select" multiple size="8">
                @foreach ($allPermissions as $permission)
                    <option value="{{ $permission }}" @selected(in_array($permission, old('denied_permissions', $staff->denied_permissions ?? []), true))>{{ $permission }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <input type="hidden" name="staff_active" value="0">
            <label class="form-check">
                <input class="form-check-input" type="checkbox" name="staff_active" value="1" @checked(old('staff_active', $staff->staff_active ?? true))>
                <span class="form-check-label">Active staff account</span>
            </label>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-success">{{ ($creating ?? false) ? 'Create Staff' : 'Update Staff' }}</button>
    </div>
</form>
@endsection
