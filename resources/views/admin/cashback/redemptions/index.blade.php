@extends('layouts.admin')
@section('title','Cashback Redemptions')
@section('admin-content')
<h1 class="h3 mb-4">Cashback Redemptions</h1>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Customer</th><th>Requested</th><th>Approved</th><th>Status</th><th>Coupon</th><th></th></tr></thead><tbody>@forelse($redemptions as $redemption)<tr><td>{{ $redemption->customer?->name }}<div class="small text-muted">{{ $redemption->customer?->mobile }}</div></td><td>Rs. {{ number_format((float)$redemption->requested_amount,2) }}</td><td>Rs. {{ number_format((float)$redemption->approved_amount,2) }}</td><td>{{ $redemption->status }}</td><td>{{ $redemption->coupon?->code ?: '-' }}</td><td><a href="{{ route('admin.cashback.redemptions.show',$redemption) }}" class="btn btn-sm btn-outline-success">View</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No requests.</td></tr>@endforelse</tbody></table></div><div class="card-footer bg-white">{{ $redemptions->links() }}</div></div>
@endsection
