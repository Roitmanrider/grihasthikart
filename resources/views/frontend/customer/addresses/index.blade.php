@extends('layouts.frontend')
@section('title','My Addresses')
@section('content')
<section class="py-5"><div class="container">
<h1 class="h3 mb-4">My Addresses</h1>
<div class="row g-4"><div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-header bg-white fw-semibold">Add Address</div><div class="card-body"><form method="POST" action="{{ route('customer.addresses.store') }}" class="row g-3">@csrf @include('frontend.customer.addresses.form', ['address'=>null])<div class="col-12"><button class="btn btn-success">Save Address</button></div></form></div></div></div>
<div class="col-lg-7">@forelse($addresses as $address)<div class="card border-0 shadow-sm mb-3"><div class="card-body"><div class="fw-semibold">{{ $address->label ?: 'Address' }} {{ $address->is_default ? '(Default)' : '' }}</div><div class="text-muted">{{ $address->address_line1 }}, {{ $address->city }} - {{ $address->pincode }}</div><div class="small mt-1">{{ $address->is_approved ? 'Approved' : 'Pending approval' }}</div><div class="mt-2"><a href="{{ route('customer.addresses.edit',$address) }}" class="btn btn-sm btn-outline-success">Edit</a><form method="POST" action="{{ route('customer.addresses.default',$address) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">Default</button></form><form method="POST" action="{{ route('customer.addresses.destroy',$address) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></div></div></div>@empty<div class="alert alert-light border">No addresses saved.</div>@endforelse</div></div>
</div></section>
@endsection
