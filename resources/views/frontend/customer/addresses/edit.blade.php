@extends('layouts.frontend')
@section('title','Edit Address')
@section('content')
<section class="py-5"><div class="container" style="max-width: 760px;"><div class="d-flex justify-content-between mb-4"><h1 class="h3">Edit Address</h1><a href="{{ route('customer.addresses.index') }}" class="btn btn-outline-secondary">Back</a></div><div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('customer.addresses.update',$address) }}" class="row g-3">@csrf @method('PATCH') @include('frontend.customer.addresses.form', ['address'=>$address])<div class="col-12"><button class="btn btn-success">Update Address</button></div></form></div></div></div></section>
@endsection
