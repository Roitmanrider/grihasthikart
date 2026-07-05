@extends('layouts.admin')

@section('title', 'Coupons')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Coupons</h1>
        <div class="text-muted">Manage checkout discount codes and usage limits.</div>
    </div>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-success">Add Coupon</a>
</div>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body row g-3">
        <div class="col-md-3"><input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Code, name, customer"></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value="">All status</option><option value="1" @selected(request('status') === '1')>Active</option><option value="0" @selected(request('status') === '0')>Inactive</option></select></div>
        <div class="col-md-2"><select name="discount_type" class="form-select"><option value="">All types</option><option value="fixed" @selected(request('discount_type') === 'fixed')>Fixed</option><option value="percentage" @selected(request('discount_type') === 'percentage')>Percentage</option></select></div>
        <div class="col-md-2"><select name="validity" class="form-select"><option value="">Any validity</option><option value="active" @selected(request('validity') === 'active')>Current</option><option value="expired" @selected(request('validity') === 'expired')>Expired</option><option value="upcoming" @selected(request('validity') === 'upcoming')>Upcoming</option></select></div>
        <div class="col-md-2"><select name="trashed" class="form-select"><option value="">Without deleted</option><option value="with" @selected(request('trashed') === 'with')>With deleted</option><option value="only" @selected(request('trashed') === 'only')>Deleted only</option></select></div>
        <div class="col-md-1"><button class="btn btn-outline-success w-100">Filter</button></div>
    </div>
</form>

<form method="POST" action="{{ route('admin.coupons.bulk-action', request()->query()) }}">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white row g-2">
            <div class="col-md-3"><select name="action" class="form-select"><option value="">Bulk action</option><option value="activate">Mark Active</option><option value="deactivate">Mark Inactive</option><option value="delete">Delete</option><option value="restore">Restore</option></select></div>
            <div class="col-md-auto"><button class="btn btn-outline-secondary">Apply</button></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.coupon-check').forEach((el) => el.checked = this.checked)"></th><th>Code</th><th>Discount</th><th>Min Order</th><th>Usage</th><th>Status</th><th>Validity</th><th></th></tr></thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr @class(['table-warning' => $coupon->trashed()])>
                            <td><input type="checkbox" name="ids[]" value="{{ $coupon->id }}" class="form-check-input coupon-check"></td>
                            <td><div class="fw-semibold">{{ $coupon->code }}</div><div class="small text-muted">{{ $coupon->name }}</div></td>
                            <td>{{ str($coupon->discount_type)->headline() }} / {{ number_format((float) $coupon->discount_value, 2) }}</td>
                            <td>Rs. {{ number_format((float) $coupon->minimum_order_amount, 2) }}</td>
                            <td>{{ $coupon->usages_count }}</td>
                            <td><span class="badge {{ $coupon->status ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $coupon->status ? 'Active' : 'Inactive' }}</span></td>
                            <td class="small text-muted">{{ $coupon->starts_at?->format('d M Y') ?: 'Now' }} - {{ $coupon->expires_at?->format('d M Y') ?: 'No expiry' }}</td>
                            <td class="text-end">
                                @if ($coupon->trashed())
                                    <form method="POST" action="{{ route('admin.coupons.restore', $coupon->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Restore</button></form>
                                @else
                                    <a href="{{ route('admin.coupons.show', $coupon) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-success">Edit</a>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No coupons found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $coupons->links() }}</div>
    </div>
</form>
@endsection
