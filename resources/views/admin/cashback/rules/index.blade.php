@extends('layouts.admin')
@section('title','Cashback Rules')
@section('admin-content')
<div class="d-flex justify-content-between mb-4"><h1 class="h3">Cashback Rules</h1><a href="{{ route('admin.cashback.rules.create') }}" class="btn btn-success">Add Rule</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Name</th><th>Percent</th><th>Monthly Threshold</th><th>Category %</th><th>Multiple</th><th>Status</th><th></th></tr></thead><tbody>@foreach($rules as $rule)<tr><td>{{ $rule->name }} @if($rule->is_default)<span class="badge text-bg-success">Default</span>@endif</td><td>{{ $rule->cashback_percent }}%</td><td>Rs. {{ number_format((float)$rule->monthly_order_threshold,2) }}</td><td>{{ $rule->eligible_category_threshold_percent }}%</td><td>Rs. {{ number_format((float)$rule->redemption_multiple,2) }}</td><td>{{ $rule->status ? 'Active' : 'Inactive' }}</td><td><a href="{{ route('admin.cashback.rules.edit',$rule) }}" class="btn btn-sm btn-outline-success">Edit</a></td></tr>@endforeach</tbody></table></div><div class="card-footer bg-white">{{ $rules->links() }}</div></div>
@endsection
