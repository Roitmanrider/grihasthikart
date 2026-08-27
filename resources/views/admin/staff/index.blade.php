@extends('layouts.admin')

@section('title', 'Staff')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Staff</h1>
        <div class="text-muted">Assign admin roles and operational store access.</div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Store</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <div>{{ str($user->role ?: 'unassigned')->replace('_', ' ')->headline() }}</div>
                            @if ($user->staff_roles)
                                <div class="small text-muted">{{ collect($user->staff_roles)->map(fn ($role) => str($role)->replace('_', ' ')->headline())->join(', ') }}</div>
                            @endif
                        </td>
                        <td>{{ $user->assignedStore?->name ?: 'All Stores' }}</td>
                        <td class="text-end"><a href="{{ route('admin.staff.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No staff found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $staff->links() }}</div>
@endsection
